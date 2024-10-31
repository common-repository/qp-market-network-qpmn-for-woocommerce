<?php

namespace QPMN\Partner\Libs\HttpClient\Obj;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * 
 * @property string $firstName   
 * @property string $lastName   
 * @property string $state   
 * @property string $city   
 * @property string $postcode   
 * @property string $address1   
 * @property string $address2   
 * @property string $phone   
 * @property string $email   
 * @property string $country
 * 
 */
class QPMNBillingAddress extends Base
{
    public string $firstName;
    public string $lastName;
    public string $state;
    public string $city;
    public string $postcode;
    public string $address1 = '';
    public string $address2 = '';
    public string $phone;
    public string $email = '';
    public string $country;

    public function CGPData()
    {
        return [
            "first_name" => $this->firstName,
            "last_name" => $this->lastName,
            "state" => $this->state,
            "postcode" => $this->postcode,
            "address_1" => $this->address1,
            "address_2" => $this->address2,
            "phone" => $this->phone,
            "email" => $this->email,
            "country" => $this->country,
        ];
    }
}
