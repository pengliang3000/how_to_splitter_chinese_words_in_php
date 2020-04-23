# How to splitter Chinese words in php

## Setup
- copy codes below into a file (maybe like: test.php )
- get results as execut "php test.php “”
```
<?php
$file = "d:\\aa.dict";
$tagModel = TagMaster::getInstance($file);
$tagModel->saveDics([
    [
        'tag' => 'bigshow',
        'goods_id' => '111'
    ],
    [
        'tag' => '含量',
        'goods_id' => '122'
    ],
    [
        'tag' => 'a星',
        'goods_id' => '13'
    ],
    [
        'tag' => 'sk2',
        'goods_id' => '16'
    ],
    [
        'tag' => 'sk4',
        'goods_id' => '16'
    ],
    [
        'tag' => '水',
        'goods_id' => 20
    ]
]);
$data = $tagModel->parseWords("中水诺biGshowSk2忆江南  含量杭州hnag州44  含量   sk4 怕A星说");
//$data = $tagModel->parseWords("");
print_r($data);
?>
```
