<?php

class ABCD_Notifier {
    private $recipients = array();
    private $uid,$root,$mgr,$message,$sender;
    
    private function init() {
        $this->debug = FALSE;
        $this->uid  = Zend_Registry::get('uid');
        $this->root = Zend_Registry::get('root');
        $this->mgr  = Zend_Registry::get('mgr');
    }
    
    protected function _checkValid($type,$entity,$group) {
        if (!in_array($entity,$group)) {
            throw exception ("Invalid $type $entity passed to notifier");
        }
    }
    
    protected function _setRecipients($type,array $ids) {
        $validTypes = array('email'); //future functionality will include 'internal'
        $this->_checkValid('notification type',$type,$validTypes);
        
        if ($type == 'email') {
            $userModel = new Application_Model_DbTable_Users;
            $allIDs = $userModel->getIDs();
            
            foreach ($ids as $id) {
                $this->_checkValid('User ID',$id,$allIDs);
                $email = $userModel->getEmail($id);
                array_push($this->recipients,$email);
            }
        } else {
            array_push($this->recipients,$id);
        }
    }
    
    protected function _setMessage($message) {
        $this->message = $message;
    }
    
    protected function _setSender($sender) {
        $this->sender = $sender;
    }
    
    public function send(array $recipients,$message,$sender, $type='email') {
        $this->_setRecipients($type,$recipients);
        $this->_setSender($sender);
        $this->_setMessage($message);
        
        $mail = new Zend_Mail();
        $mail->setBodyText($this->message);
        $mail->setFrom($this->sender);
        $mail->setSubject("Foo haha!");
        
        foreach ($this->recipients as $address) {
         $mail->addTo($address);   
        }
        
        $mail->send();   
        //print_r($mail);
    }
}