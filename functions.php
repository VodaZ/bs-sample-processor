<?php
require "config.php";

function print_array($ar)
{
    echo "{\n";
    foreach ($ar as $key => $val) {
        echo "\t[" . $key . "] => " . $val . "\n";
    }
    echo "}\n";
}

function getFolderName($root, $colNum){
    global $collectionName;
    $folders = scandir($root);
    $folder = current(array_filter($folders, function($folder) use ($colNum) {
        return $colNum === intval($folder);
    }));

    $startPos = strpos($folder, ' - ') + 3;
    $endPos = strlen($folder);
    $collectionName = substr($folder, $startPos, $endPos);

    return $root . $folder;
}

function getImagesSize($arr)
{
    $x = null;
    $y = null;

    foreach ($arr as $img) {
        $info = getimagesize($img);

        if ($x == null) $x = $info[0];
        if ($y == null) $y = $info[1];

        if ($x != $info[0] || $y != $info[1]) return null;
    }

    return Array(
        "x" => $x,
        "y" => $y
    );
}

function getSamples($folderName){
    $rawContent = scandir($folderName);
    $filteredContent = array_filter($rawContent, function($el){
        return substr($el, 0, 3) == "Col" || substr($el, 0, 3) == "col";
    });

    $filteredContent = array_map(function($imgName){
        global $folderName;
        return $folderName . "/" . $imgName;
    } , $filteredContent);

    return array_values($filteredContent);
}

function getNumberImages(){
    $rawNumbers = array_filter(scandir("numbers"), function($num){
        return substr($num, 0, 1) != '.';
    });

    $numberImages = array_map(function($num){
        return "numbers/" . $num;
    }, $rawNumbers);

    $numberImages = array_values($numberImages);

    $imageObjects = array_map(function($path){
        return [
            "path" => $path,
            "obj" => @imagecreatefromjpeg($path)
        ];

    }, $numberImages);

    return $imageObjects;
}

function getSampleNumber($filePath){
    $name = basename($filePath);

    $spacePos = strpos($name, " ");
    $dotPos = strpos($name, ".");

    return substr($name, $spacePos + 1, $dotPos - $spacePos - 1);
}

function compoundNumber($val){
    global $numberImages;
    $numberSize = getimagesize($numberImages[0]["path"]);

    $outputSize = [
        "x" => strlen($val) * $numberSize[0],
        "y" => $numberSize[1]
    ];

    $outputImage = imagecreatetruecolor($outputSize["x"], $outputSize["y"]);

    $white = imagecolorallocate($outputImage, 255, 255, 255);
    imagefilledrectangle($outputImage, 0, 0, $outputSize["x"] - 1, $outputSize["y"] - 1, $white);

    //print_array($numberImages);

    for($i = 0; $i < strlen($val); $i++){
        imagecopy($outputImage, $numberImages[$val[$i]]["obj"], $i * $numberSize[0], 0, 0, 0, $numberSize[0], $numberSize[1]);
    }

    return [
        "img" => $outputImage,
        "dim" => $outputSize
    ];
}

function getFileType($path){
    $pathLen = strlen($path);

    $a = strpos($path, '.', $pathLen - 6);
    $b = strpos($path, '.', $pathLen - 6);
    $dotPos = $a ? $a : $b;

    $extension = substr($path, $dotPos + 1, $pathLen);

    if(strtolower($extension) === 'jpeg' || strtolower($extension) === 'jpg'){
        return 'jpg';
    }

    return $extension;
}

function createImgFromArray($filteredSamples){
    global $newImgSize;
    global $imgsPerPage;
    global $margin;
    global $spacing;
    global $imageSize;
    global $newImage;
    global $procentualMarginOfLegend;

    for($i = 0; $i < $imgsPerPage; $i++){
        switch (getFileType($filteredSamples[$i])){
            case 'jpg':
                $modelImg = @imagecreatefromjpeg($filteredSamples[$i]);
                break;
            case 'png':
                $modelImg = @imagecreatefrompng($filteredSamples[$i]);
        }

        if(!$modelImg) break;

        $x_dst = $margin["LR"];
        $y_dst = $margin["TB"] + ($imageSize["y"] + $spacing) * $i;

        imagecopy($newImage, $modelImg, $x_dst, $y_dst, 0, 0, $imageSize["x"], $imageSize["y"]);

        $sampleNumber = getSampleNumber($filteredSamples[$i]);
        $sampleNumberImg = compoundNumber($sampleNumber);

        $resizeFactor = $sampleNumberImg["dim"]["y"] / ($spacing * (1 - 2 * $procentualMarginOfLegend));

        $x_number = $sampleNumberImg["dim"]["x"] / $resizeFactor;
        $y_number = $sampleNumberImg["dim"]["y"] / $resizeFactor;

        imagecopyresized($newImage, $sampleNumberImg["img"], -20, $y_dst + $imageSize["y"] + $spacing * $procentualMarginOfLegend, 0, 0, $x_number, $y_number, $sampleNumberImg["dim"]["x"], $sampleNumberImg["dim"]["y"]);

        imagedestroy($modelImg);
        imagedestroy($sampleNumberImg["img"]);
    }
}

?>