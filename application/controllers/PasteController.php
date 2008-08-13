<?php
/**
 * Pastebin application
 * 
 * @uses      Zend_Controller_Action
 * @package   Paste
 * @author    Matthew Weier O'Phinney <matthew@weierophinney.net> 
 * @copyright Copyright (C) 2008 - Present, Matthew Weier O'Phinney
 * @license   New BSD {@link http://framework.zend.com/license/new-bsd}
 * @version   $Id: $
 */
class PasteController extends Zend_Controller_Action
{
    protected $_model;

    /**
     * Pre-Dispatch: set up dojo and context switching
     * 
     * @return void
     */
    public function preDispatch()
    {
        $request = $this->getRequest();

        Zend_Dojo_View_Helper_Dojo::setUseDeclarative();
        $contextSwitch = $this->_helper->contextSwitch;
        $contextSwitch->addContext('ajax', array('suffix' => 'ajax'))
                      ->addActionContext('new', 'ajax')
                      ->addActionContext('followup', 'ajax')
                      ->addActionContext('display', 'ajax')
                      ->addActionContext('active', 'ajax')
                      ->addActionContext('active-data', 'ajax')
                      ->initContext();

        $this->view->dojo()->registerModulePath('paste', '../paste')
                           ->requireModule('paste.main')
                           ->addOnLoad('paste.main.init');

        $message = array(
            'Current request information',
            array(
                array('Module', 'Controller', 'Action'),
                array($request->getModuleName(), $request->getControllerName(), $request->getActionName()),
            )
        );

        Zend_Registry::get('log')->table($message);
    }

    /**
     * Landing page
     * 
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * New Paste page
     * 
     * @return void
     */
    public function newAction()
    {
        $form = $this->getForm();
        $this->view->form = $form;
    }

    /**
     * Save paste
     * 
     * @return void
     */
    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_helper->redirector('new');
        }

        $form = $this->getForm();
        if (!$form->isValid($request->getPost($form->getElementsBelongTo()))) {
            $this->view->form = $form;
            return $this->render('new');
        }

        $data = $form->getValues();
        $data = $data['pasteform'];
        $model = $this->getModel();
        $id = $model->add($data);
        $this->_helper->redirector('display', null, null, array('id' => $id));
    }

    /**
     * Display paste
     * 
     * @return void
     */
    public function displayAction()
    {
        if (!$id = $this->_getParam('id', false)) {
            return $this->_helper->redirector('index');
        }

        $model = $this->getModel();
        if (!$paste = $model->get($id)) {$view = Zend_Layout::getMvcInstance()->getView();
            $this->view->title   = 'Not Found';
            $this->view->message = "Paste not found";
            return;
        }

        $this->view->id    = $id;
        $this->view->title = $id;
        $this->view->paste = $paste;
    }

    /**
     * Follow-up paste form
     * 
     * @return void
     */
    public function followupAction()
    {
        if (!$id = $this->_getParam('id', false)) {
            return $this->_helper->redirector('index');
        }

        $model = $this->getModel();
        if (!$paste = $model->get($id)) {$view = Zend_Layout::getMvcInstance()->getView();
            $this->view->title   = 'Not Found';
            $this->view->message = "Paste not found";
            return;
        }
        $this->view->id = $id;

        $followupKeys = array(
            'code'    => null,
            'type'    => null,
            'summary' => null,
        );
        $followup = array_intersect_key($paste, $followupKeys);
        $followup['parent'] = $id;

        $form = $this->getFollowupForm($id);
        $form->setDefaults($followup);

        $this->view->title = 'Followup: ' . $id;
        $this->view->form  = $form;
    }

    /**
     * Process followup
     * 
     * @return void
     */
    public function saveFollowupAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_helper->redirector('new');
        }

        if (!$parentId = $this->_getParam('id', false)) {
            return $this->_helper->redirector('index');
        }

        $form = $this->getFollowupForm($parentId);
        if (!$form->isValid($request->getPost($form->getElementsBelongTo()))) {
            $this->view->form  = $form;
            return $this->render('followup');
        }

        $data  = $form->getValues();
        $data  = $data[$form->getElementsBelongTo()];
        $model = $this->getModel();
        $id    = $model->add($data);
        $this->_helper->redirector('display', null, null, array('id' => $id));
    }

    public function activeAction()
    {
    }

    public function activeDataAction()
    {
        $model = $this->getModel();
        $dojoData = new Zend_Dojo_Data('id', $model->fetchActive(), 'id');
        $this->view->data = $dojoData;
    }

    /**
     * Helper method: get paste model 
     * 
     * @return Paste
     */
    public function getModel()
    {
        if (null === $this->_model) {
            $this->_model = new Paste();
        }
        return $this->_model;
    }

    /**
     * Helper method: get new paste form
     * 
     * @return PasteForm
     */
    public function getForm()
    {
        require_once dirname(__FILE__) . '/../forms/PasteForm.php';
        return new PasteForm(array('action' => '/paste/save', 'method' => 'post'));
    }

    /**
     * Helper method: get followup paste form
     * 
     * @param  string $id 
     * @return PasteForm
     */
    public function getFollowupForm($id)
    {
        $form = $this->getForm();
        $form->addElement('hidden', 'parent', array(
                 'required' => true,
                 'validators' => array(
                     new Zend_Validate_Identical($id),
                 ),
             ))
             ->setName('followupform')
             ->setElementsBelongTo('followupform')
             ->setAction('/paste/save-followup/id/' . $id);
        $form->save->setDijitParam('onClick', 'paste.main.followupPasteButton');
        return $form;
    }
}
