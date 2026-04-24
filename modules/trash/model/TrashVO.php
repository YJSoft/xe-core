<?php

class TrashVO
{
	public $trashSrl;
	public $title;
	public $originModule;
	public $serializedObject;
	public $unserializedObject;
	public $description;
	public $ipaddress;
	public $removerSrl;
	public $userId;
	public $nickName;
	public $regdate;

	public function getTrashSrl()
	{
		return $this->trashSrl;
	}
	public function setTrashSrl($trashSrl)
	{
		$this->trashSrl = $trashSrl;
	}
	public function getTitle()
	{
		if(empty($this->title)) return $lang->untitle;
		return htmlspecialchars($this->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}
	public function setTitle($title)
	{
		$this->title = $title;
	}
	public function getOriginModule()
	{
		if(empty($this->originModule)) return 'document';
		return $this->originModule;
	}
	public function setOriginModule($originModule)
	{
		$this->originModule = $originModule;
	}
	public function getSerializedObject()
	{
		return $this->serializedObject;
	}
	public function setSerializedObject($serializedObject)
	{
		$this->serializedObject = $serializedObject;
	}
	public function getUnserializedObject()
	{
		return $this->unserializedObject;
	}
	public function setUnserializedObject($serializedObject)
	{
		$this->unserializedObject = unserialize($serializedObject);
	}
	public function getDescription()
	{
		return htmlspecialchars($this->description, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}
	public function setDescription($description)
	{
		$this->description = $description;
	}
	public function getIpaddress()
	{
		return $this->ipaddress;
	}
	public function setIpaddress($ipaddress)
	{
		$this->ipaddress = $ipaddress;
	}
	public function getRemoverSrl()
	{
		return $this->removerSrl;
	}
	public function setRemoverSrl($removerSrl)
	{
		$this->removerSrl = $removerSrl;
	}
	public function getUserId()
	{
		return $this->userId;
	}
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}
	public function getNickName()
	{
		return htmlspecialchars($this->nickName, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}
	public function setNickName($nickName)
	{
		$this->nickName = $nickName;
	}
	public function getRegdate()
	{
		if(empty($this->regdate)) return date('YmdHis');

		return $this->regdate;
	}
	public function setRegdate($regdate)
	{
		$this->regdate = $regdate;
	}
}

/* End of file Trash.php */
/* Location: ./modules/trash/model/Trash.php */
