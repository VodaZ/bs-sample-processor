<?php
    require "functions.php";

    $folderName = getFolderName($rootFolder, $colNumber);

    echo "Editting: " . $folderName . "\n";

    $filteredSamples = getSamples($folderName); // get images of samples

    $numberImages = getNumberImages();          // get images of numbers

    $imageSize = getImagesSize($filteredSamples);

    if($imageSize == null) die("Obrázky mají různou velikost");

    $newImgSize = Array(
        "x" => $imageSize["x"] + 2 * $margin["LR"],
        "y" => $imageSize["y"] * $imgsPerPage + $spacing * $imgsPerPage + 2 * $margin["TB"]
    );

    $noOfNewImages = ceil(count($filteredSamples) / $imgsPerPage);

    mkdir($folderName . "/new");

    for($i = 0; $i <  $noOfNewImages; $i++){
        $newImage = imagecreatetruecolor($newImgSize["x"], $newImgSize["y"]);

        $white = imagecolorallocate($newImage, 255, 255, 255);
        $black = imagecolorallocate($newImage, 0, 0, 0);

        imagefilledrectangle($newImage, 0, 0, $newImgSize["x"] - 1, $newImgSize["y"] - 1, $white);

        $source = array_slice($filteredSamples, $i * $imgsPerPage, $imgsPerPage);
        createImgFromArray($source);

        imagejpeg($newImage, $folderName.'/new/'.$collectionName.'-'.$i.'.jpg');

        imagecolordeallocate($newImage, $white);
        imagecolordeallocate($newImage, $black);
        imagedestroy($newImage);
    }

    foreach($numberImages as $im){
        imagedestroy($im["obj"]);
    }
?>