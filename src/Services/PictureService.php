<?php

namespace App\Services;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{
    public function __construct(
        private ParameterBagInterface $params
    )
    {}

    public function square(UploadedFile $picture, ?string $folder = '', ?int $width = 250): string
    {

        $file = md5(uniqid(rand(), true)) . '.webp';


        $pictureInfos = getimagesize($picture);

        if($pictureInfos === false){
            throw new Exception('Image Format Incorrect');
        }


        switch($pictureInfos['mime']){
            case 'image/png':
                $sourcePicture = imagecreatefrompng($picture);
                break;
            case 'image/jpeg':
                $sourcePicture = imagecreatefromjpeg($picture);
                break;
            case 'image/webp':
                $sourcePicture = imagecreatefromwebp($picture);
                break;
            default:
                throw new Exception('Image Format Incorrect');
        }


        $imageWidth = $pictureInfos[0];
        $imageHeight = $pictureInfos[1];

        switch($imageWidth <=> $imageHeight){
            case -1:
                $squareSize = $imageWidth;
                $srcX = 0;
                $srcY = ($imageHeight - $imageWidth) / 2;
                break;

            case 0:
                $squareSize = $imageWidth;
                $srcX = 0;
                $srcY = 0;
                break;

            case 1:
                $squareSize = $imageHeight;
                $srcX = ($imageWidth - $imageHeight) / 2;
                $srcY = 0;
                break;
        }


        $resizedPicture = imagecreatetruecolor($width, $width);

        imagecopyresampled($resizedPicture, $sourcePicture, 0, 0, $srcX, $srcY, $width, $width, $squareSize, $squareSize);

        $path = $this->params->get('uploads_directory') . $folder;


        if(!file_exists($path . '/mini/')){
            mkdir($path . '/mini/', 0755, true);
        }

        imagewebp($resizedPicture, $path . '/mini/' . $width . 'x' . $width . '-' . $file);

        $picture->move($path . '/', $file);

        return $file;
    }
}