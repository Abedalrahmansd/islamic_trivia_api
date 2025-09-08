<?php
class Validator {
    private $errors = [];

    public function required($value, $field) {
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = "$field is required";
        }
        return $this;
    }

    public function minLength($value, $min, $field) {
        if (strlen($value) < $min) {
            $this->errors[$field] = "$field must be at least $min characters";
        }
        return $this;
    }

    public function maxLength($value, $max, $field) {
        if (strlen($value) > $max) {
            $this->errors[$field] = "$field must not exceed $max characters";
        }
        return $this;
    }

    public function email($value, $field) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$field must be a valid email address";
        }
        return $this;
    }

    public function inArray($value, $allowed, $field) {
        if (!in_array($value, $allowed)) {
            $this->errors[$field] = "$field must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    public function numeric($value, $field, $min = null, $max = null) {
        if (!is_numeric($value)) {
            $this->errors[$field] = "$field must be a number";
        } elseif ($min !== null && $value < $min) {
            $this->errors[$field] = "$field must be at least $min";
        } elseif ($max !== null && $value > $max) {
            $this->errors[$field] = "$field must not exceed $max";
        }
        return $this;
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function validate($rules, $data) {
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                if (is_string($rule)) {
                    $this->$rule($value, $field);
                } elseif (is_array($rule)) {
                    $method = array_shift($rule);
                    // $this->$method($value, ...$rule, $field);
                    $this->$method($value, ...array_merge($rule, [$field]));
                }
            }
        }
        
        return $this;
    }
}
?>