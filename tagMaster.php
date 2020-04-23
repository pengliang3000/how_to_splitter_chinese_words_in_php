<?php

/**
 * 中文分词类
 * @author pengliang 2019-03-17 
 */
namespace tag;

class TagMaster {

    //中文全角字母和数字
    protected $fullWidthChars = ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９', '＋', '－', '％', '．', 'ａ', 'ｂ', 'ｃ', 'ｄ', 'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ', 'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ', 'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ', 'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ', 'ｙ', 'ｚ', 'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ', 'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ', 'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ', 'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ', 'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ', 'Ｚ'];
    protected $halfWidthChars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '-', '%', '.', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    // 标点符号  
    protected $removalChars = ["▲", "△", "▼", "▽", "★", "☆", "◆", "◇", "■", "□", "●", "○", "⊙", "㊣", "◎", "▂", "▁", "▃", "▄", "▅", "▆", "▇", "█", "▏", "▎", "▍", "▌", "▋", "▊", "◢", "◣", "◥", "◤", "▲", "▼", "♀", "♂", "卍", "※"];
    protected $removalChars2 = ["/r", "/n", "/t", '`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '+', '=', '|', '//', '/', '"', ';', ':', '/', '', '.', '>', ',', '<', '[', '{', ']', '}', '·', '～', '！', '＠', '＃', '￥', '％', '……', '＆', '×', '（', '）', '－', '——', '＝', '＋', '＼', '｜', '【', '｛', '】', '｝', '‘', '“', '”', '；', '：', '、', '？', '。', '》', '，', '《', ','];
    //默认词分割字符
    protected $wordsSplitChar = ' ';
    //换行字符
    protected $lineChar = "\r\n";
    //字典内容分割符
    protected $colSplitChar = ' ';
    //商品编号分隔符
    protected $goodsSplitChar = ',';
    //词库信息:自贸区 11 22 33 33 444
    protected $dics = [];
    protected $file = "";
    protected static $instance = null;
    //匹配词最长字符
    protected $maxLengthForWord = 16;

    protected function __construct($file) {
        
        $this->file = $file ? $file :config('tag_store_file');
        $this->loadDict($this->file);
    }

    function __destruct() {
        unset($this->dics);
    }

    /*
     * 全圆字符转半圆
     * 标点符号转分隔符
     */

    public static function getInstance($file) {
        if (self::$instance == null) {
            self::$instance = new TagMaster($file);
        }
        return self::$instance;
    }

    public function cleanUp($words) {
        $words = strtolower($words);
        $words = str_replace($this->fullWidthChars, $this->halfWidthChars, $words);
        $words = str_replace($this->removalChars, $this->wordsSplitChar, $words);
        $words = str_replace($this->removalChars2, $this->wordsSplitChar, $words);
        return $words;
    }

    //字典内存中是否存在该TAG
    public function inDics($words) {
        if (isset($this->dics[$words])) {
            return $this->dics[$words];
        } else {
            return false;
        }
    }

    /*
     * 保存TAG到dics中
     * [
     *   [
     *     'tag'=> '精华水',
     *     'goods_id' => 158
     *   ],
     * ]
     */

    public function saveDics($tags) {
        $hasChanged = false;
        if ($tags && is_array($tags)) {
            foreach ($tags as $val) {
                $val['tag'] = strtolower($val['tag']);
                if (isset($this->dics[$val['tag']])) {
                    $tmpArr = explode($this->goodsSplitChar, $this->dics[$val['tag']]);
                    if (in_array($val['goods_id'], $tmpArr)) {
                        continue;
                    } else {
                        $tmpArr[] = $val['goods_id'];
                        $this->dics[$val['tag']] = implode($this->goodsSplitChar, $tmpArr);
                        $hasChanged = true;
                    }
                } else {
                    $this->dics[$val['tag']] = $val['goods_id'];
                    $hasChanged = true;
                }
            }
        }
        if ($hasChanged) {
            $fd = fopen($this->file, "w");
            foreach ($this->dics as $key => $val) {
                fwrite($fd, $key . " " . $val . $this->lineChar);
            }
            fclose($fd);
        }
    }
    
    /**
     * 删除TAG中所有的goods_id
     * @param type $goods_id
     */
    public function deleteGoodsId($goods_id){
        $hasChanged = false;
        if($this->dics && is_array($this->dics)){
            foreach($this->dics as $key=>$val){
                 $tmpArr = explode($this->goodsSplitChar, $val);
                 $idx = array_search($goods_id,$tmpArr);
                 if ($idx !== false) {
                     unset($tmpArr[$idx]);
                     $hasChanged = true;
                     $this->dics[$key] = implode($this->goodsSplitChar, $tmpArr);
                 }
            }
        }
        if ($hasChanged) {
            $fd = fopen($this->file, "w");
            foreach ($this->dics as $key => $val) {
                fwrite($fd, $key . " " . $val . $this->lineChar);
            }
            fclose($fd);
        }
    }

    //解析词
    public function parseWords($words) {
        $startTime = microtime(true);
        $words = $this->cleanUp($words);
        $strlen = strlen($words);
        $result = [];
        $tmpWord = [];
        $j = 0;
        // var_dump($words);
        for ($i = 0; $i < $strlen; $i++) {
            //3字节字符
            if (ord($words[$i]) >= 0x81) {
                $tmpWord[$j][] = $words[$i] . $words[$i + 1] . $words[$i + 2];
                $i += 2;
            } else {//单字节字符
                if ($words[$i] == $this->wordsSplitChar || ord($words[$i]) < 32) {
                    //$tmpWord[$j][] = $this->wordsSplitChar;
                    $j++;
                } else {
                    $tmpWord[$j][] = $words[$i];
                }
            }
        }

        //var_dump($tmpWord);
        //exit;
        if ($tmpWord && is_array($tmpWord)) {
            foreach ($tmpWord as $val) {
                if ($val) {
                    $tpKey = "";
                    $len = sizeof($val);
                    for ($j = $len -1; $j >= 0;) {
                        $val = array_splice($val,0,$j+1);
                        //var_dump($val);
                        for ($i = 0; $i <= $j; $i++) {
                            $tpKey = implode('',array_slice($val, $i));
                            
                            //判断TAG是否在字典存在
                            $keyRes = $this->inDics($tpKey);
                            if ($keyRes !== false) {
                                $result[] = [
                                    'tag' => $tpKey,
                                    'goods_id' => $keyRes
                                ];
                                $j = $i;
                                break;
                            }
                        }
                        $j--;
                    }
                }
            }
        }
        $endTime = microtime(true);
        #var_dump((float) ($endTime - $startTime));
        return $result;
    }

    //加载字典文件到内存
    public function loadDict($file) {
        if (!file_exists($file)) {
            throw new Exception("file do not exits :" . $file);
        }
        $data = file_get_contents($file);
        if ($data === false) {
            throw new Exception("can not read file :" . $file);
        }
        if ($data) {
            $lines = explode($this->lineChar, $data);
            if ($lines && is_array($lines)) {
                foreach ($lines as $key => $line) {
                    $line = explode($this->colSplitChar, $line);
                    if ($line[0])
                        $this->dics[$line[0]] = isset($line[1]) ? $line[1] : '';
                }
            }
            unset($lines, $line);
        }else {
            $this->dics = [];
        }
    }

}
