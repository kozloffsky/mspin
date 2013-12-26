<?php
/**
 * Class for avatar manipulations eg saving creating modifying.
 *
 */
class Moodspin_Avatar_Manager
{
    protected static $_instance;
    
    protected $_basePath;
    protected $_avatarDirectoryName = "avatars";
    protected $_modifiedAvatarsDirectoryName = 'modified';
    protected $_originalAvatarsDirectoryName = 'original';
    protected $_baseUrl;
    protected $_defaultAvatarFileName = "default.png";
    protected $_defaultTwitterAvatarFileName = "default_twitter.png";
    protected $_moodResource;
    protected $_imgResource;
    protected $_identifyPreffix = '___moodspin___';
    
    protected $_originalAvatarsSize = array('width'=>73,'height'=>73);
    
    /**
     * Singleton
     *
     * @return Moodspin_Avatar_Manager
     */
    public static function getInstance ()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * protected constructor
     *
     */
    protected function __construct (){}
    
    /**
     * Base Path
     *
     * @return string
     */
    public function getBasePath ()
    {
        return $this->_basePath;
    }
    
    public function setBasePath ($value)
    {
        $this->_basePath = $value;
    }
    
    /**
     * Base url
     *
     * @return string
     */
    public function getBaseUrl ()
    {
        return $this->_baseUrl;
    }
    public function setBaseUrl ($value)
    {
        $this->_baseUrl = $value;
    }
    
    /**
     * Directory on file system where stored avatars
     *
     * @return string
     */
    public function getAvatarsDirectory ()
    {
        return $this->getBasePath() . '/' . $this->_avatarDirectoryName;
    }
    
    /**
     * Base url to avatars
     *
     * @return string
     */
    public function getAvatarsUrl ()
    {
        return $this->getBaseUrl() . '/' . $this->_avatarDirectoryName;
    }
    
    /**
     * Directory where stored modified avatars
     *
     * @return string
     */
    public function getModifiedAvatarsDirectoryName ()
    {
        return $this->_modifiedAvatarsDirectoryName;
    }
    
    public function setModifiedAvatarsDirectoryName ($value)
    {
        $this->_modifiedAvatarsDirectoryName = $value;
    }
    
    /**
     * Directory where stored original avatars
     *
     * @return string
     */
    public function getOriginalAvatarsDirectoryName ()
    {
        return $this->_originalAvatarsDirectoryName;
    }
    
    public function setOriginalAvatarsDirectoryName ($value)
    {
        $this->_originalAvatarsDirectoryName = $value;
    }
    
    /**
     * Name of default avatar file name
     *
     * @return string
     */
    public function getDefaultAvatarFileName ()
    {
        return $this->_defaultAvatarFileName;
    }
    
    public function setDefaultAvatarFileName ($value)
    {
        $this->_defaultAvatarFileName = $value;
    }
    
    /**
     * Url to default avatar
     *
     * @return string
     */
    public function getDefaultAvatarUrl ()
    {
        return $this->getAvatarsUrl() . '/' . $this->getDefaultAvatarFileName();
    }
    
    /**
     * Save original avatar from given url for user $user
     *
     * @param Moodspin_User $user
     * @param string $remoteUrl
     * @param string $ext
     * @return string
     */
    public function saveOriginalAvatar ($user, $remoteUrl, $ext = "png", $saveToModified=false)
    {
        if ($user instanceof Moodspin_User ){
            $screenName = $user->getScreenName();
        } elseif (is_string($user)) {
            $screenName = $user;
        } else {
            return false;
        }

        if ($this->isImageOriginal($remoteUrl)) {
            $imagePath = $this->getOriginalAvatarPathForUser($screenName, $ext);

            $image = $this->getImage($remoteUrl);
            $newImage = imagecreatetruecolor($this->_originalAvatarsSize['width'],$this->_originalAvatarsSize['height']);
            imagealphablending($newImage, true);
            imagefilledrectangle($newImage,0,0,imagesx($image),imagesy($image),imagecolorallocate($newImage,255,255,255));
            
            imagecopyresized(
            	$newImage,
            	$image,
            	0,0,0,0,
            	$this->_originalAvatarsSize['width'],
            	$this->_originalAvatarsSize['height'],
            	imagesx($image),
            	imagesy($image)
            );

            imagepng($newImage, $imagePath, 0);

            if($saveToModified){
            	$imagePath = $this->getModifyedAvatarPathForUser($screenName);
            	imagepng($newImage, $imagePath, 0);
            }
            
            Moodspin_Log::log('saving image from ' . $remoteUrl);
        } else {
            /* TODO: Refactoring is required. Durty workarounds!!!. */
            $imagePath = $this->getModifyedAvatarPathForUser($screenName);
            if (file_exists($imagePath) === false) {
                if ($image = file_get_contents($remoteUrl)) {
	                file_put_contents($imagePath, $image);
                }
            }

            $imagePath = $this->getOriginalAvatarPathForUser($screenName, $ext);
            if (file_exists($imagePath) === false) {
                $remoteUrl = $this->getAvatarsDirectory() . '/' . $this->_defaultTwitterAvatarFileName;
                $image = file_get_contents($remoteUrl);
                file_put_contents($imagePath, $image);
            }
        }
        return $this->getOriginalAvatarUrlForUser($screenName);
    }
    
    public function saveAvatarToModified($user, $remoteUrl)
    {
        $screenName = $user;
        
        $imagePath = $this->getModifyedAvatarPathForUser($screenName);
        
        /*$image = file_get_contents($remoteUrl);
        file_put_contents($imagePath,$image);*/
        $image = $this->getImage($remoteUrl);
        $newImage = imagecreatetruecolor(imagesx($image),imagesy($image));
        imagealphablending($newImage, true);
        imagefilledrectangle($newImage,0,0,imagesx($image),imagesy($image),imagecolorallocate($newImage,255,255,255));
        imagecopy($newImage,$image,0,0,0,0,imagesx($image),imagesy($image));
        imagejpeg($newImage, $imagePath, 100);
        Moodspin_Log::log('saving image from ' . $remoteUrl);
        
        return $this->getOriginalAvatarUrlForUser($screenName);
    }
    
    /**
     * If avatar on given path was created with manager than return false
     *
     * @param string $path
     * @return boolean
     */
    public function isImageOriginal ($path)
    {
        $parsedUrl = parse_url($path);
        $fileName = pathinfo($parsedUrl['path']);
        $fileName = $fileName['filename'];
        if (strpos($fileName, $this->_identifyPreffix) === false) {
            return true;
        }
        return false;
    }
    
    /**
     * return path to users original avatar
     *
     * @param string $userName
     * @param string $ext
     * @return string
     */
    public function getOriginalAvatarPathForUser ($userName, $ext = 'png')
    {
        return $this->getAvatarsDirectory() . '/' . $this->getOriginalAvatarsDirectoryName() . '/' . $userName . '.' . $ext;
    }
    
    /**
     * Url for original avatar for user $user
     *
     * @param string $userName
     * @param string $ext
     * @return string
     */
    public function getOriginalAvatarUrlForUser ($userName, $ext = 'png')
    {
        return $this->getAvatarsUrl() . '/' . $this->getOriginalAvatarsDirectoryName() . '/' . $userName . '.png';
    }
    
    public function getModifiedAvatarUrlForUser($userName)
    {
        return $this->getAvatarsUrl() . '/' . $this->getModifiedAvatarsDirectoryName() . '/' . $this->_identifyPreffix .  $userName . '.jpeg';
    }
    
    /**
     * returns modified avatars path for user $userName
     *
     * @param strung $userName
     * @return string
     */
    public function getModifyedAvatarPathForUser ($userName)
    {
        return $this->getAvatarsDirectory() . '/' . $this->getModifiedAvatarsDirectoryName() . '/' . $this->_identifyPreffix . $userName . '.jpeg';
    }
    
    /**
     * Adds overlay on avatar
     *
     * @param string $userName
     * @param integer $moodId
     */
    public function addMoodForUser ($userName, $moodId)
    {
        $imgPath = $this->getOriginalAvatarPathForUser($userName);
        $avatar = $this->getImage($imgPath);
        $mood = $this->getMoodOverlay($moodId);
        imagesavealpha($mood, true);
        $res = imagecreatetruecolor(imagesx($avatar), imagesy($avatar));
        imagefilledrectangle($res,0,0,imagesx($res),imagesy($res),imagecolorallocate($res,255,255,255));
        imagealphablending($res, true);
        imagecopy($res, $avatar, 0, 0, 0, 0, imagesx($avatar), imagesy($avatar));
        $copyResult = imagecopyresampled($res, $mood, 0, 0, 0, 0, imagesx($avatar), imagesy($avatar), imagesx($mood), imagesy($mood));
        $writeResult = imagejpeg($res, $this->getModifyedAvatarPathForUser($userName), 100);
        //$writeResult = imagepng($res, $this->getModifyedAvatarPathForUser($userName),0);
        if ($copyResult == false || $writeResult == false) {
            throw new Exception("Fail to apply mood to avatar");
        }
    }
    
    /**
     * returns path to overlay file for $moodId
     *
     * @param unknown_type $moodId
     * @return unknown
     */
    public function getMoodOverlay ($moodId)
    {
        $moodUrl = realpath($this->getBasePath() . '/moods/overlay_' . $moodId . '.png');
        
        if (getimagesize($moodUrl) == FALSE) {
            throw new Exception('Bad MoodId ' . $moodId);
        }
        
        $img = &$this->getImage($moodUrl);
        return $img;
    }
    
    /**
     * loads image from given $path
     *
     * @param string $path
     * @return GD Resource
     */
    public function getImage ($path)
    {
        $info = getimagesize($path);
        
        if ($info === FALSE) {
            throw new Exception('Can`t create image from path ' . $path . '; File Not Found');
        }
        
        $mime = $info[2];
        
        switch ($mime) {
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
                break;
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
                break;
            case IMAGETYPE_BMP:
                return $this->_imageCreateFromBMP($path);
                break;
        }
        
        return false;
    }
    
    /**
     * Information about image in given path
     *
     * @param unknown_type $path
     * @return unknown
     */
    public function getImageInfo ($path)
    {
        $info = getimagesize($path);
        
        if ($info === false) {
            throw new Exception('Bad image path ' . $path);
        }
        
        return $info;
    }

    /**
     * get large image url by small one
     * (just replace 'bigger' to 'normal'
     * in image name)
     * @param string $imageUrl
     * @return string
     */
    public function getLargeImageUrl ($imageUrl)
    {
        return preg_replace("~normal(\.\w{3,4})$~is","bigger\\1",$imageUrl);
    }
    
    function _imageCreateFromBMP($filename) {
        if (! $f1 = fopen ( $filename, "rb" )) {
            return false;
        }

        $file = unpack ( "vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread ( $f1, 14 ) );
        
        if ($file ['file_type'] != 19778) {
            return false;
        }
        
        $bmp = unpack ( 'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread ( $f1, 40 ) );
        $bmp ['colors'] = pow ( 2, $bmp ['bits_per_pixel'] );
        if ($bmp ['size_bitmap'] == 0)
            $bmp ['size_bitmap'] = $file ['file_size'] - $file ['bitmap_offset'];
        $bmp ['bytes_per_pixel'] = $bmp ['bits_per_pixel'] / 8;
        $bmp ['bytes_per_pixel2'] = ceil ( $bmp ['bytes_per_pixel'] );
        $bmp ['decal'] = ($bmp ['width'] * $bmp ['bytes_per_pixel'] / 4);
        $bmp ['decal'] -= floor ( $bmp ['width'] * $bmp ['bytes_per_pixel'] / 4 );
        $bmp ['decal'] = 4 - (4 * $bmp ['decal']);
        if ($bmp ['decal'] == 4)
            $bmp ['decal'] = 0;

        $palette = array ();
        if ($bmp ['colors'] < 16777216) {
            $palette = unpack ( 'V' . $bmp ['colors'], fread ( $f1, $bmp ['colors'] * 4 ) );
        }
        
        $img = file_get_contents ( $filename, 'rb', null, 0, $bmp ['size_bitmap'] );
        $vide = chr ( 0 );
        
        $res = imagecreatetruecolor ( $bmp ['width'], $bmp ['height'] );
        $p = 0;
        $y = $bmp ['height'] - 1;
        while ( $y >= 0 ) {
            $x = 0;
            while ( $x < $bmp ['width'] ) {
                if ($bmp ['bits_per_pixel'] == 24) {
                    $data = substr ( $img, $p, 3 );
                    $data .= $vide;
                    
                    if (strlen($data) >= 4) {
                        $color = unpack ( "V",  $data);
                    } else {
                        $color = array(0,0);
                    }
                } elseif ($bmp ['bits_per_pixel'] == 16) {
                    $data = substr ( $img, $p, 2 );
                    if (strlen($data) >= 2) {
                       $color = unpack ( "n", $data );
                    } else {
                        $color = array(0,0);
                    }
                    $color [1] = $palette [$color [1] + 1];
                } elseif ($bmp ['bits_per_pixel'] == 8) {
                    $color = unpack ( "n", $vide . substr ( $img, $p, 1 ) );
                    $color [1] = $palette [$color [1] + 1];
                } elseif ($bmp ['bits_per_pixel'] == 4) {
                    $color = unpack ( "n", $vide . substr ( $img, floor ( $p ), 1 ) );
                    if (($p * 2) % 2 == 0)
                        $color [1] = ($color [1] >> 4);
                    else
                        $color [1] = ($color [1] & 0x0F);
                    $color [1] = $palette [$color [1] + 1];
                } elseif ($bmp ['bits_per_pixel'] == 1) {
                    $color = unpack ( "n", $vide . substr ( $img, floor ( $p ), 1 ) );
                    if (($p * 8) % 8 == 0)
                        $color [1] = $color [1] >> 7;
                    elseif (($p * 8) % 8 == 1)
                        $color [1] = ($color [1] & 0x40) >> 6;
                    elseif (($p * 8) % 8 == 2)
                        $color [1] = ($color [1] & 0x20) >> 5;
                    elseif (($p * 8) % 8 == 3)
                        $color [1] = ($color [1] & 0x10) >> 4;
                    elseif (($p * 8) % 8 == 4)
                        $color [1] = ($color [1] & 0x8) >> 3;
                    elseif (($p * 8) % 8 == 5)
                        $color [1] = ($color [1] & 0x4) >> 2;
                    elseif (($p * 8) % 8 == 6)
                        $color [1] = ($color [1] & 0x2) >> 1;
                    elseif (($p * 8) % 8 == 7)
                        $color [1] = ($color [1] & 0x1);
                    $color [1] = $palette [$color [1] + 1];
                } else
                    return false;
                imagesetpixel ( $res, $x, $y, $color [1] );
                $x ++;
                $p += $bmp ['bytes_per_pixel'];
            }
            $y --;
            $p += $bmp ['decal'];
        }
        
        fclose ( $f1 );
        return $res;
    }

    public function assignAvatar(Array $usersList) {
        foreach ($usersList as &$user) {
            $avatar = $this->getModifiedAvatarUrlForUser($user['login']);
            $user['avatar'] = $avatar;
        }

        return $usersList;
    }
}
