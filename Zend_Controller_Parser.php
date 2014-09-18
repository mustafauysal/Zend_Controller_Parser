<?php

/**
 * Description: Parse Zend project and fetch all module/controller/action. If you're using ACL, may help you.
 * Author: mustafauysal
 * Version: 0.2
 * License: GPLv2 (or later)
 */
class Zend_Controller_Parser
{

    protected static $directory = '.';
    public $result;

    function __construct($directory = null)
    {

        if ($directory)
            self::$directory = $directory;


     $this->parseControllers();
    }


    /**
     * @param $directory
     * @param bool $recursive
     * @param bool $listDirs
     * @param bool $listFiles
     * @param string $exclude
     * @return array
     *
     * @see  http://php.net/manual/en/function.scandir.php#109140
     */
    public function directoryToArray($directory, $recursive = true, $listDirs = false, $listFiles = true, $exclude = '')
    {
        $arrayItems = array();
        $skipByExclude = false;
        $handle = opendir($directory);


        if ($handle) {
            while (false !== ($file = readdir($handle))) {
                preg_match("/(^(([\.]){1,2})$|(\.(svn|git|md))|(Thumbs\.db|\.DS_STORE))$/iu", $file, $skip);
                if ($exclude) {
                    preg_match($exclude, $file, $skipByExclude);
                }
                if (!$skip && !$skipByExclude) {
                    if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                        if ($recursive) {
                            $arrayItems = array_merge($arrayItems, $this->directoryToArray($directory . DIRECTORY_SEPARATOR . $file, $recursive, $listDirs, $listFiles, $exclude));
                        }
                        if ($listDirs) {
                            $file = $directory . DIRECTORY_SEPARATOR . $file;
                            $arrayItems[] = $file;
                        }
                    } else {
                        if ($listFiles) {
                            $file = $directory . DIRECTORY_SEPARATOR . $file;
                            $arrayItems[] = $file;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $arrayItems;
    }

    /**
     * Preparing Controllers
     * We'll use for fetch content of each file.
     * @return array
     */
    private function  prepareControllers()
    {
        $files = $this->directoryToArray(self::$directory);
        foreach ($files as $path) {
            if (preg_match('/controllers\/(.+)(Controller.php)$/m', $path)) {
                $controllers[] = $path;
            }
        }

        return $controllers;
    }


    /**
     * Parse Controller files, find module, controllers and actions
     * @return mixed
     */
    private function parseControllers()
    {
        $controllers = $this->prepareControllers();

        foreach ($controllers as $key => $value) {

            $file = file_get_contents($value);
            preg_match('/\bclass(.+)extends/ms', $file, $data);
            if ($data != null && $data != "") {

                preg_match_all('/function\s(.+)Action/', $file, $actions);

                $split = explode("_", trim($data[1]));

                if (count($split) == 2) {
                    $result[$key]["module"] = $split[0];
                    $result[$key]["controller"] = str_replace("Controller", '', $split[1]);
                } else {
                    $result[$key]["module"] = "default";
                    $result[$key]["controller"] = str_replace("Controller", '', $split[0]);
                }
                $result[$key]["actions"] = $actions[1];


            }

        }


        foreach ($result as $key => $group) {
            $newArr[$group["module"]][$group["controller"]] = $group["actions"];
        }


        $this->result = $newArr;

        return $this->result;
    }

}

//
// Example
//
//if (isset($_SERVER['argc'])) {
//    if($argv[1] && $argv[1] != ""){
//        $test = new Zend_Controller_Parser($argv[1]);
//        var_export($test->result);
//    }
//
//}else{
//    $test = new Zend_Controller_Parser('../');
//    var_export($test->result);
//}
//
