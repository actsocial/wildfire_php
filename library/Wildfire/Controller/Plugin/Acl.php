<?php
class Wildfire_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // set up acl
        $acl = new Zend_Acl();
        
        // add the roles
        $acl->addRole(new Zend_Acl_Role('guest'));
        $acl->addRole(new Zend_Acl_Role('consumer'), 'guest');
        $acl->addRole(new Zend_Acl_Role('administrator'), 'consumer');
        $acl->addRole(new Zend_Acl_Role('client'));
        
        // add the resources
        $acl->add(new Zend_Acl_Resource('index'));
        $acl->add(new Zend_Acl_Resource('error'));
        $acl->add(new Zend_Acl_Resource('admin'));
        $acl->add(new Zend_Acl_Resource('campaign'));
        $acl->add(new Zend_Acl_Resource('campaigninvitation'));
        $acl->add(new Zend_Acl_Resource('campaignparticipation'));
        $acl->add(new Zend_Acl_Resource('client'));
        $acl->add(new Zend_Acl_Resource('consumer'));
        $acl->add(new Zend_Acl_Resource('conversation'));
        $acl->add(new Zend_Acl_Resource('dashboard'));
        $acl->add(new Zend_Acl_Resource('forgetpassword'));
        $acl->add(new Zend_Acl_Resource('gift'));
        $acl->add(new Zend_Acl_Resource('history'));
        $acl->add(new Zend_Acl_Resource('home'));
        $acl->add(new Zend_Acl_Resource('login'));

        // set up the access rules
        $acl->allow(null, array('index', 'error'));
        // a guest can only login
        $acl->allow('guest', 'index', array('index', 'loginfailed'));
        $acl->allow('guest', 'login', array('login'));
        $acl->allow('guest', 'forgetpassword', array('index', 'sendsms', 'sendemail'));
        // consumer
        $acl->allow('consumer', 'gift', array('list', 'addtocart', 'cart', 'listorder'));
        // administrators can do anything
        $acl->allow('administrator', null);
        
        // fetch the current user
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()) {
            $authNamespace = new Zend_Session_Namespace('Zend_Auth');
            $role = $authNamespace->role;
        } else {
            $role = 'guest';
        }
        
        $controller = $request->controller;
        $action = $request->action;
        
        if (!$acl->isAllowed($role, $controller, $action)) {
            if ($role == 'guest') {
                $request->setControllerName('index');
                $request->setActionName('index');
            } else {
               $request->setControllerName('error');
               $request->setActionName('noauth');
            }
        }
    }
    
}
