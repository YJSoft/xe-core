<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

/**
 * File abstraction class 
 *
 * @author XEHub (developers@xpressengine.com)
 */
class FileObject extends BaseObject
{

	/**
	 * File descriptor
	 * @var resource
	 */
	public $fp = NULL;

	/**
	 * File path
	 * @var string
	 */
	public $path = NULL;

	/**
	 * File open mode
	 * @var string
	 */
	public $mode = "r";

	/**
	 * Constructor 
	 *
	 * @param string $path Path of target file
	 * @param string $mode File open mode 
	 * @return void
	 */
	public function __construct($path, $mode)
	{
		if($path != NULL)
		{
			$this->Open($path, $mode);
		}
	}

	/**
	 * Append target file's content to current file 
	 *
	 * @param string $file_name Path of target file
	 * @return void 
	 */
	public function append($file_name)
	{
		$target = new FileObject($file_name, "r");
		while(!$target->feof())
		{
			$readstr = $target->read();
			$this->write($readstr);
		}
		$target->close();
	}

	/**
	 * Check current file meets eof
	 *
	 * @return bool true: if eof. false: otherwise 
	 */
	public function feof()
	{
		return feof($this->fp);
	}

	/**
	 * Read from current file 
	 *
	 * @param int $size Size to read
	 * @return string Returns the read string or false on failure.
	 */
	public function read($size = 1024)
	{
		return fread($this->fp, $size);
	}

	/**
	 * Write string to current file 
	 *
	 * @param string $str String to write
	 * @return int Returns the number of bytes written, or false on error.
	 */
	public function write($str)
	{
		$len = strlen($str);
		if(!$str || $len <= 0)
		{
			return FALSE;
		}
		if(!$this->fp)
		{
			return FALSE;
		}
		$written = fwrite($this->fp, $str);
		return $written;
	}

	/**
	 * Open a file
	 *
	 * If file is opened, close it and open the new path
	 *
	 * @param string $path Path of target file
	 * @param string $mode File open mode (http://php.net/manual/en/function.fopen.php)
	 * @return bool true if succeed, false otherwise.
	 */
	public function open($path, $mode)
	{
		if($this->fp != NULL)
		{
			$this->close();
		}
		$this->fp = fopen($path, $mode);
		if(!is_resource($this->fp))
		{
			$this->fp = NULL;
			return FALSE;
		}
		$this->path = $path;
		return TRUE;
	}

	/**
	 * Return current file's path
	 *
	 * @return string Returns the path of current file.
	 */
	public function getPath()
	{
		if($this->fp != NULL)
		{
			return $this->path;
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Close file 
	 *
	 * @return void
	 */
	public function close()
	{
		if($this->fp != NULL)
		{
			fclose($this->fp);
			$this->fp = NULL;
		}
	}

}
/* End of file FileObject.class.php */
/* Location: ./classes/file/FileObject.class.php */
