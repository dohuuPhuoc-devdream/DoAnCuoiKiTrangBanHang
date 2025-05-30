<?php

class User {
    private $id;
    private $userName;
    private $email;
    private $phone;
    private $city;
    private $district;
    private $ward;
    private $street;
    private $image;
    private $isAdmin;
    private $password;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->userName = $data['userName'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->district = $data['district'] ?? '';
        $this->ward = $data['ward'] ?? '';
        $this->street = $data['street'] ?? '';
        $this->image = $data['image'] ?? null;
        $this->isAdmin = $data['isAdmin'] ?? false;
        $this->password= $data['password'] ?? '';
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserName() { return $this->userName; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getCity() { return $this->city; }
    public function getDistrict() { return $this->district; }
    public function getWard() { return $this->ward; }
    public function getStreet() { return $this->street; }
    public function getImage() { return $this->image; }
    public function getisAdmin() { return $this->isAdmin; }
    public function getPassword() { return $this->password; }

    // Setters
    public function setUserName($userName) { $this->userName = $userName; }
    public function setEmail($email) { $this->email = $email; }
    public function setPhone($phone) { $this->phone = $phone; }
    public function setCity($city) { $this->city = $city; }
    public function setDistrict($district) { $this->district = $district; }
    public function setWard($ward) { $this->ward = $ward; }
    public function setStreet($street) { $this->street = $street; }
    public function setImage($image) { $this->image = $image; }
    public function setIsAdmin($isAdmin) { $this->isAdmin = $isAdmin; }
    public function setPassword($password) { $this->password = $password; }

    public function toArray() {
        return [
            'id' => $this->id,
            'userName' => $this->userName,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'district' => $this->district,
            'ward' => $this->ward,
            'street' => $this->street,
            'image' => $this->image,
            'isAdmin' => $this->isAdmin,
            'password' => $this->password
        ];
    }
} 