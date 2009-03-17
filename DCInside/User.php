<?php

final class DCInside_User {
    public $name;
    public $url;

    function __construct($name, $url = null) {
        $this->name = $name;
        $this->url = $url;
    }

    function __toString() {
        return $this->name;
    }
}

