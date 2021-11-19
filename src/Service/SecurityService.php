<?php

namespace jjansen\Service;

class SecurityService
{

    public function __construct()
    {
    }

    public function checkInputPW(string $input): bool
    {
        // ## returns true = inputs is fine, returns false = input is not valid
        // checks if password matches pattern
        if (!preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#$%])[0-9A-Za-z!@#$%]{8,50}$/", $input)) {
            return false;
        }
        return true;
    }

    public function checkInputEmail(string $email): bool
    {
        // extract mail domain
        $host = substr($email, (strpos($email, "@") + 1));
        // check if domain really exists
        if (!checkdnsrr($host, "MX")) {
            return false;
        }
        return true;
    }

    public function checkInput(string $input): bool
    {
        // ## returns true = inputs is fine, returns false = input is not valid
        // checks if inputs is empty
        if (empty($input) || preg_match("/[\[^\'£$%^&*()}{@:\'#~?><>,;@\|\\-=\-_+\-¬\`\]]/", $input)) {
            return false;
        }
        return true;
    }

    public function checkInputNumber(int $input): bool
    {
        // ## returns true = inputs is fine, returns false = input is not valid
        // checks if inputs is empty
        if (empty($input) || !is_numeric($input) || $input <= 0) {
            return false;
        }
        return true;
    }

    public function checkInputOptional(string $input): bool
    {
        // ## returns true = inputs is fine, returns false = input is not valid
        // checks if inputs is empty
        if (preg_match("/[\[^\'£$%^&*()}{@:\'#~?><>,;@\|\\-=\-_+\-¬\`\]]/", $input)) {
            return false;
        }

        return true;
    }

    public function checkInputPWMatch(string $pwd1, string $pwd2): bool
    {
        if ($pwd1 != $pwd2) {
            return false;
        }
        return true;
    }

    public function filterInput(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    public function filterInputEmail(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }

    public function checkInputQuickID(?string $input): string
    {

        if ($input == null) {
            return $this->generateQuickID();
        }
        // check if Quick-ID starts with #
        if (!strpos($input, '#') === 0) {
            return $this->generateQuickID();
        }
        //check if Quick-ID has needed length
        if (!strlen($input) == 11) {
            return $this->generateQuickID();
        }
        // check default format #12345ABCDE
        // check if pos 2-5 are numbers
        if (!is_numeric(substr($input, 1, 5))) {
            return $this->generateQuickID();
        }
        // check if pos 6-9 are letters
        if (!is_string(substr($input, 6, 9))) {
            return $this->generateQuickID();
        }
        return $input;
    }

    public function checkSelect(string $input, array $allowed): bool
    {

        // return false if array is empty
        if (count($allowed) <= 0) {
            return false;
        }
        // check if input is inside of allowed array selects
        if (!in_array($input, $allowed)) {
            return false;
        }
        return true;
    }

    private function generateQuickID(): string
    {
        // returns a QuickID - Format: #1234ABCD
        $quick_id = "#";
        // add random numbers 1234
        $quick_id = $quick_id . rand(10000, 20000);
        // add random letters
        $allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $quick_id = $quick_id . substr(str_shuffle($allowed_chars), 0, 5);

        // return generated quickid
        return $quick_id;
    }

}