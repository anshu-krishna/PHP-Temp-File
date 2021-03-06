<?php
namespace Krishna;

use Exception;

class TempFile {
	public readonly string $filename;
	public bool $persist = false;
	private $fp;
	private static function guid() {
		// Get an RFC-4122 compliant globaly unique identifier
		$data = openssl_random_pseudo_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
	private static function error_handler(int $errno, string $errstr, string $errfile, int $errline) {}
	public function __construct(string $dir, string $ext = 'temp') {
		$real_dir = realpath($dir);
		if($real_dir === false) {
			throw new Exception("Directory '{$dir}' used for TempFile does not exist");
		}
		do {
			$filename = "{$real_dir}/" . static::guid() . ".{$ext}";
		} while(file_exists($filename));
		$this->filename = $filename;
		$old = set_error_handler([static::class, 'error_handler']);
		$this->fp = fopen($this->filename, 'w+');
		set_error_handler($old);
		if($this->fp === false) {
			throw new Exception("Unable to create TempFile '{$this->filename}'");
		}
	}
	public function __destruct() {
		$old = set_error_handler([static::class, 'error_handler']);
		fsync($this->fp);
		fclose($this->fp);
		if(!$this->persist) {
			unlink($this->filename);
		}
		set_error_handler($old);
	}
	public function write(string $data, ?int $length = null): int|false {
		return fwrite($this->fp, $data, $length);
	}
	public function read(int $length): string|false {
		return fread($this->fp, $length);
	}
	public function tell(): int|false {
		return ftell($this->fp);
	}
	public function seek(int $offset, int $whence = SEEK_SET): int {
		/*
		Whence: 
			SEEK_SET - Set position equal to offset bytes.
			SEEK_CUR - Set position to current location plus offset.
			SEEK_END - Set position to end-of-file plus offset.
		*/
		return fseek($this->fp, $offset, $whence);
	}
	public function sync(): bool {
		return fsync($this->fp);
	}
	public function get_lines(int $flags = 0): array|false {
		/*
		Flags: FILE_IGNORE_NEW_LINES, FILE_SKIP_EMPTY_LINES
		*/
		$old = set_error_handler([static::class, 'error_handler']);
		$lines = file($this->filename, $flags);
		set_error_handler($old);
		return $lines;
	}
	public function  get_contents(int $offset = 0, ?int $length = null): string|false {
		$old = set_error_handler([static::class, 'error_handler']);
		$content = file_get_contents(
			filename: $this->filename,
			offset: $offset,
			length: $length,
		);
		set_error_handler($old);
		return $content;
	}
	public function put_contents(mixed $data, int $flags = 0): int|false {
		/*
		Flags: FILE_APPEND, LOCK_EX
		*/
		return file_put_contents($this->filename, $data, $flags);
	}
	public function size(): int|false {
		$old = set_error_handler([static::class, 'error_handler']);
		$size = filesize($this->filename);
		set_error_handler($old);
		return $size;
	}
}