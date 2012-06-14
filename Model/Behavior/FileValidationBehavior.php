<?php
/**
 * FileValidationBehavior
 *
 * A CakePHP Behavior that adds validation model rules to file uploading.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/cakephp/uploader
 */

App::import('Vendor', 'Uploader.Uploader');

class FileValidationBehavior extends ModelBehavior {

	/**
	 * Current settings.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * Default list of validation sets.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_validations = array(
		'width' => array(
			'rule' => array('width'),
			'message' => 'Your image width is invalid; required width is %s.'
		),
		'height' => array(
			'rule' => array('height'),
			'message' => 'Your image height is invalid; required height is %s.'
		),
		'minWidth' => array(
			'rule' => array('minWidth'),
			'message' => 'Your image width is too small; minimum width %s.'
		),
		'minHeight' => array(
			'rule' => array('minHeight'),
			'message' => 'Your image height is too small; minimum height %s.'
		),
		'maxWidth' => array(
			'rule' => array('maxWidth'),
			'message' => 'Your image width is too large; maximum width %s.'
		),
		'maxHeight' => array(
			'rule' => array('maxHeight'),
			'message' => 'Your image height is too large; maximum height %s.'
		),
		'filesize' => array(
			'rule' => array('filesize'),
			'message' => 'Your filesize is too large; maximum size %s.'
		),
		'extension' => array(
			'rule' => array('extension'),
			'message' => 'Your file type is not allowed; allowed types: %s.'
		),
		'required' => array(
			'rule' => array('required'),
			'message' => 'This file is required.'
		)
	);

	/**
	 * Setup the validation and model settings.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $settings
	 * @return void
	 */
	public function setup(Model $model, $settings = array()) {
		if (!empty($settings)) {
			foreach ($settings as $field => $options) {
				$this->_settings[$model->alias][$field] = $options + array('required' => true);
			}
		}
	}

	/**
	 * Validates an image filesize. Default max size is 5 MB.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function filesize(Model $model, $data, $size = 5242880) {
		if (empty($size) || !is_numeric($size)) {
			$size = 5242880;
		}

		foreach ($data as $fieldName => $field) {
			if ($this->_allowEmpty($model, $fieldName, $field)) {
				return true;

			} else if (empty($field['tmp_name'])) {
				return false;
			}

			return ($field['size'] <= $size);
		}

		return true;
	}

	/**
	 * Checks that the image height is exact.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function height(Model $model, $data, $size = 100) {
		return $this->_validateImage($model, $data, 'height', $size);
	}

	/**
	 * Checks that the image width is exact.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function width(Model $model, $data, $size = 100) {
		return $this->_validateImage($model, $data, 'width', $size);
	}

	/**
	 * Checks the maximum image height.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function maxHeight(Model $model, $data, $size = 100) {
		return $this->_validateImage($model, $data, 'maxHeight', $size);
	}

	/**
	 * Checks the maximum image width.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function maxWidth(Model $model, $data, $size = 100) {
		return $this->_validateImage($model, $data, 'maxWidth', $size);
	}

	/**
	 * Checks the minimum image height.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function minHeight(Model $model, $data, $size = 100) {
		return $this->_validateImage($model, $data, 'minHeight', $size);
	}

	/**
	 * Checks the minimum image width.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param int $size
	 * @return boolean
	 */
	public function minWidth(Model $model, $data, $size = 100) {
		return $this->_validateImage($model, $data, 'minWidth', $size);
	}

	/**
	 * Validates the ext and mimetype.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @param array $allowed
	 * @return boolean
	 */
	public function extension(Model $model, $data, array $allowed = array()) {
		foreach ($data as $fieldName => $field) {
			if ($this->_allowEmpty($model, $fieldName, $field)) {
				return true;

			} else if (empty($field['tmp_name'])) {
				return false;

			} else {
				$ext = Uploader::ext($field['name']);
			}

			return (Uploader::checkMimeType($ext, $field['type']) && (empty($allowed) || in_array($ext, $allowed)));
		}

		return true;
	}

	/**
	 * Makes sure a file field is required and not optional.
	 *
	 * @access public
	 * @param Model $model
	 * @param array $data
	 * @return boolean
	 */
	public function required(Model $model, $data) {
		foreach ($data as $fieldName => $field) {
			$required = $this->_settings[$model->alias][$fieldName]['required'];

			if (is_array($required)) {
				$required = $required['value'];
			}

			if ($required && (!is_array($field) || empty($field['tmp_name']))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build the validation rules and validate.
	 *
	 * @access public
	 * @param Model $model
	 * @return boolean
	 */
	public function beforeValidate(Model $model) {
		if (!empty($this->_settings[$model->alias])) {
			foreach ($this->_settings[$model->alias] as $field => $rules) {
				$validations = array();

				foreach ($rules as $rule => $setting) {
					$set = $this->_validations[$rule];
					$arg = '';

					if (is_array($setting) && !isset($setting[0])) {
						if (!empty($setting['error'])) {
							$set['message'] = $setting['error'];
						}

						switch ($rule) {
							case 'required':
								$set['rule'] = array($rule);
							break;
							case 'extension':
								$arg = (array) $setting['value'];
								$set['rule'] = array($rule, $arg);
							break;
							default:
								$arg = (int) $setting['value'];
								$set['rule'] = array($rule, $arg);
							break;
						}
					} else {
						$set['rule'] = array($rule, $setting);
						$arg = $setting;
					}

					if (isset($rules['required'])) {
						if (is_array($rules['required'])) {
							$set['allowEmpty'] = !(bool) $rules['required']['value'];
						} else {
							$set['allowEmpty'] = !(bool) $rules['required'];
						}
					}

					if (is_array($arg)) {
						$arg = implode(', ', $arg);
					}

					$set['message'] = __d('uploader', $set['message'], $arg);
					$validations[$rule] = $set;
				}

				if (!empty($validations)) {
					if (!empty($model->validate[$field])) {
						$validations = $validations + $model->validate[$field];
					}

					$model->validate[$field] = $validations;
				}
			}
		}

		return true;
	}

	/**
	 * Allow empty file uploads to circumvent file validations.
	 *
	 * @access protected
	 * @param Model $model
	 * @param string $fieldName
	 * @param array $field
	 * @return bool
	 */
	protected function _allowEmpty(Model $model, $fieldName, $field) {
		$required = false;

		if (isset($this->_settings[$model->alias][$fieldName]['required'])) {
			$required = $this->_settings[$model->alias][$fieldName]['required'];

			if (is_array($required)) {
				$required = $required['value'];
			}
		}

		return (!$required && empty($field['tmp_name']));
	}

	/**
	 * Validates multiple combinations of height and width for an image.
	 *
	 * @access protected
	 * @param Model $model
	 * @param array $data
	 * @param string $type
	 * @param int $size
	 * @return boolean
	 */
	protected function _validateImage(Model $model, $data, $type, $size = 100) {
		foreach ($data as $fieldName => $field) {
			if ($this->_allowEmpty($model, $fieldName, $field)) {
				return true;

			} else if (empty($field['tmp_name'])) {
				return false;
			}

			$file = getimagesize($field['tmp_name']);

			if (!$file) {
				return false;
			}

			$width = $file[0];
			$height = $file[1];

			switch ($type) {
				case 'width':		return ($width == $size); break;
				case 'height':		return ($height == $size); break;
				case 'maxWidth':	return ($width <= $size); break;
				case 'maxHeight':	return ($height <= $size); break;
				case 'minWidth':	return ($width >= $size); break;
				case 'minHeight':	return ($height >= $size); break;
			}
		}

		return true;
	}

}
