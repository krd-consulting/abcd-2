<?php

class Application_Form_Search extends Zend_Form {
    
    protected $role = 0;
    protected $multiOptions = array();
    
    public function setRole($role) 
    {
        $this->role = $role;
        
    }
    
    public function multiOptions(){
        $this->multiOptions = array('participant' => 'Participants', 
                                   'group' => 'Groups', 
                                   'staff' => 'Staff');        
        return $this->multiOptions;
    }
    
    public function init() 
    {
        $this->setName('search');
        $this->setMethod('post');
        
        $this->addElement('radio', 'acType', array(
            'required' => true,
            'attribs'  => array(
                'id' => 'acType'
            ),
            'multioptions' => $this->multiOptions(),
            'separator' => '',
            'class' => 'search-types',
            'value' => 'participant'
        ));
        
                
        $this->addElement('text', 'searchkey', array(
            'attribs' => array(
                            'id' => 'searchkey'
                        ),
            'class' => 'dash-search'
        ));
        
        $this->addElement('submit', 'Go', array(
            'label' => 'Go',
            'class' => 'abcd-submit',
        ));
        
        $this->searchkey->addDecorator('HtmlTag', array(
            'tag' => 'div',
            'class' => 'searchbox'
        ));
        
                
    }
}
