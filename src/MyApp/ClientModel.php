<?php
/**
 * Created by PhpStorm.
 * User: nexce_000
 * Date: 21.07.2016
 * Time: 10:44
 */

namespace MyApp;


class ClientModel
{
    public $auth;
    public $name;

    /**
     * ClientModel constructor.
     * @param bool $auth
     * @param string $name
     */
    public function __construct(bool $auth = false, string $name = null)
    {
        $this->auth = $auth;
        $this->name = $name;
    }


}
