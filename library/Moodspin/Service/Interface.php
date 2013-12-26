<?php
interface Moodspin_Service_Interface
{
    /**
     * Update status
     *
     * @param string $text Mood status message
     * @param string $image_path Path to mood status image
     * @return mixed|null
     */
    public function updateStatus($identity, $text, $imagePath);

    /**
     * Return service name
     *
     * @return string 
     */
    public function getServiceName();

    /**
     * Logout user from service
     *
     */
    public function logout();
}