<?php
// Call PasteControllerTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "PasteControllerTest::main");
}

require_once dirname(__FILE__) . '/../TestHelper.php';

/**
 * Test class for Paste.
 *
 * @group Controllers
 */
class PasteControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @return void
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("PasteControllerTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->bootstrap = array($this, 'bootstrapPaste');
        return parent::setUp();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    public function bootstrapPaste()
    {
        include dirname(__FILE__) . '/../../scripts/loadTestDb.php';
        $this->frontController->registerPlugin(new My_Plugin_Initialize('testing'));
    }

    public function getData()
    {
        return array(
            'pasteform' => array(
                'code'    => '<?php phpinfo() ?>',
                'type'    => 'php',
                'user'    => 'matthew',
                'summary' => 'test entry',
                'expires' => 3600,
            ),
        );
    }

    public function testIndexPageShouldContainButtonToCreateNewPaste()
    {
        $this->dispatch('/paste');
        $this->assertNotRedirect();
        $this->assertQuery('.new-paste a');
    }

    public function testNewPastePageShouldProvideLanguageSelection()
    {
        $this->dispatch('/paste/new');
        $this->assertNotRedirect();
        $this->assertQuery('#pasteform-type', $this->response->getBody());
    }

    public function testNewPastePageShouldProvideUserFillin()
    {
        $this->dispatch('/paste/new');
        $this->assertNotRedirect();
        $this->assertQuery('#pasteform-user');
    }

    public function testNewPastePageShouldProvideSummaryFillin()
    {
        $this->dispatch('/paste/new');
        $this->assertNotRedirect();
        $this->assertQuery('#pasteform-summary', $this->response->getBody());
    }

    public function testNewPastePageShouldProvideCodeFillin()
    {
        $this->dispatch('/paste/new');
        $this->assertNotRedirect();
        $this->assertQuery('#pasteform-code');
    }

    public function testNewPastePageShouldProvideExpirationSelection()
    {
        $this->dispatch('/paste/new');
        $this->assertNotRedirect();
        $this->assertQuery('#pasteform-expires');
    }

    public function testSavePasteShouldRedirectToNewPasteFormWhenNonPostRequestDetected()
    {
        $this->dispatch('/paste/save');
        $this->assertRedirectTo('/paste/new');
    }

    public function testSavePasteShouldRedirectToPasteDisplayWhenSuccessful()
    {
        $this->request->setPost($this->getData())
                      ->setMethod('POST');
        $this->dispatch('/paste/save');
        $this->assertRedirectRegex('#^/paste(.*?)/[a-z0-9]{13}$#i', var_export($this->response->getBody(), 1));
    }

    public function testSavePasteShouldCreateNewPaste()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()->from('paste', array('COUNT(*)'));
        $count = $db->fetchOne($select);

        $this->request->setPost($this->getData())
                      ->setMethod('POST');
        $this->dispatch('/paste/save');
        $test = $db->fetchOne($select);
        $this->assertEquals(1, $test - $count);
    }

    public function testDisplayPasteShouldDisplayErrorMessageWhenNotFound()
    {
        $this->dispatch('/paste/display/id/bogus');
        $this->assertQuery('#paste p.error');
    }

    public function testDisplayPasteShouldDisplayPasteDetailsWhenFound()
    {
        $data  = $this->getData();
        $paste = new Paste();
        $id    = $paste->add($data['pasteform']);

        $this->dispatch('/paste/display/id/' . $id);
        $this->assertNotQuery('p.error');
        $this->assertQueryContentContains('#pastecode code', htmlentities($data['pasteform']['code']), $this->response->getBody());
        $this->assertQueryContentContains('#metadata', $data['pasteform']['user']);
    }

    public function testDisplayPasteShouldDisplayParentAndChildPastesWhenPresent()
    {
        $data = $this->getData();
        $parentData = $data = $childData = $data['pasteform'];
        $paste    = new Paste();
        $parentId = $paste->add($parentData);
        $data['parent'] = $parentId;
        $id       = $paste->add($data);
        $childData['parent'] = $id;
        $childId  = $paste->add($childData);
        $this->dispatch('/paste/display/id/' . $id);
        $this->assertNotQuery('#paste p.error');
        $this->assertQueryContentContains('#paste p.parent a', $parentId, $this->response->getBody());
        $this->assertQuery('#paste ul.children');
        $this->assertQueryContentContains('#paste ul.children', $childId, $this->response->getBody());
    }

    public function testFollowupFormShouldDisplayOriginalPasteWithinForm()
    {
        $this->testSavePasteShouldRedirectToPasteDisplayWhenSuccessful();
        
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select();
        $select->from('paste', 'id')
               ->order('created DESC');
        $paste = $db->fetchOne($select);

        $this->resetResponse();

        $this->dispatch('/paste/followup/id/' . $paste);
        $this->assertNotRedirect();
        $this->assertNotController('error');
        $this->assertQuery('#followupform');
    }

    public function testInvalidPasteIdProvidedToFollowupShouldIndicateNotFound()
    {
        $this->dispatch('/paste/followup/id/bogus');
        $this->assertQueryContentContains('#followup p', 'Paste not found', $this->response->getBody());
    }

    public function testMissingPasteIdProvidedToFollowupShouldRedirectToLanding()
    {
        $this->dispatch('/paste/followup');
        $this->assertRedirectTo('/paste');
    }

    public function testSavingFollowupShouldRedirectToNewPastePageWhenNotSubmittedViaPost()
    {
        $this->dispatch('/paste/save-followup');
        $this->assertRedirectTo('/paste/new');
    }

    public function testSavingFollowupShouldRedirectToPasteLandingPageWhenNoIdPresent()
    {
        $data = $this->getData();
        $this->request->setPost($data)
                      ->setMethod('post');
        $this->dispatch('/paste/save-followup');
        $this->assertRedirectTo('/paste');
    }

    public function testSavingFollowupShouldRedisplayFormWhenValuesAreInvalid()
    {
        $data  = $this->getData();
        $data  = $data['pasteform'];
        $model = new Paste();
        $id    = $model->add($data);

        $data['parent'] = $id;
        $data['type']   = 'bogus';
        $data           = array('followupform' => $data);

        $this->request->setPost($data)
                      ->setMethod('post');

        $this->dispatch('/paste/save-followup/id/' . $id);
        $this->assertNotRedirect(var_export($this->response->getHeaders(), 1));
        $this->assertQuery('#followupform', $this->response->getBody());
    }

    public function testSavingValidFollowupShouldRedirectToDisplayPage()
    {
        $data  = $this->getData();
        $data  = $data['pasteform'];
        $model = new Paste();
        $id    = $model->add($data);
        $data['parent'] = $id;

        $data         = array('followupform' => $data);

        $this->request->setPost($data)
                      ->setMethod('post');

        $this->dispatch('/paste/save-followup/id/' . $id);
        $this->assertRedirect(var_export($this->response->getBody(), 1));
        $this->assertRedirectRegex('#/paste/display/id/[a-z0-9]{13}$#', var_export($this->response->getHeaders(), 1));
    }

    public function testActivePastesActionShouldDisplayGrid()
    {
        $this->dispatch('/paste/active');
        $this->assertQuery('#activePastes');
    }

    public function testActiveDataShouldReturnJsonFormattedData()
    {
        $data  = $this->getData();
        $model = new Paste();
        $ids   = array();
        for ($i = 0; $i < 5; ++$i) {
            $ids[] = $model->add($data);
        }
        $this->dispatch('/paste/active-data/format/ajax');
        $content = $this->response->getBody();
        $test = Zend_Json::decode($content);
        $this->assertTrue(is_array($test), var_export($test, 1));
        $this->assertTrue(array_key_exists('items', $test), var_export($test, 1));
        foreach ($test['items'] as $item) {
            $this->assertTrue(is_array($item), var_export($item, 1));
            $this->assertTrue(array_key_exists('id', $item));
            $this->assertTrue(in_array($item['id'], $ids));
        }
    }
}

// Call PasteControllerTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "PasteControllerTest::main") {
    PasteControllerTest::main();
}
