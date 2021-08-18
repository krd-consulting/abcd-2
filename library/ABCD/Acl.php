<?php
class ABCD_Acl extends Zend_Acl {
	public function __construct()
	{
		//resources
		$this->add(new Zend_Acl_Resource(ABCD_Resources::DOWORLD));		
		$this->add(new Zend_Acl_Resource(ABCD_Resources::DOSTAFF));		
		$this->add(new Zend_Acl_Resource(ABCD_Resources::DOMANAGER));		
		$this->add(new Zend_Acl_Resource(ABCD_Resources::DOADMIN));		
		$this->add(new Zend_Acl_Resource(ABCD_Resources::DOEVAL));		

		//roles 
		$this->addRole(new Zend_Acl_Role(ABCD_Roles::GUEST));
		$this->addRole(new Zend_Acl_Role(ABCD_Roles::EVALUATOR),ABCD_Roles::GUEST);
		$this->addRole(new Zend_Acl_Role(ABCD_Roles::STAFF),ABCD_Roles::EVALUATOR);
		$this->addRole(new Zend_Acl_Role(ABCD_Roles::MANAGER),ABCD_Roles::STAFF);
		$this->addRole(new Zend_Acl_Role(ABCD_Roles::ADMIN),ABCD_Roles::MANAGER);

		//permissions

		$this->allow(ABCD_Roles::GUEST, ABCD_Resources::DOWORLD);
		$this->allow(ABCD_Roles::STAFF, ABCD_Resources::DOSTAFF);
		$this->allow(ABCD_Roles::MANAGER, ABCD_Resources::DOMANAGER);
		$this->allow(ABCD_Roles::ADMIN, ABCD_Resources::DOADMIN);
		$this->allow(ABCD_Roles::EVALUATOR, ABCD_Resources::DOEVAL);

	}


}
