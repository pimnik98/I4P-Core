<?php
// Powered by Piminoff Nikita
// by pimnik98
// https://github.com/pimnik98/INIStorage
class INIStorage
{
    private $file  = "";
    private $array = array(array(),array());
    private $mode  = self::MODE_NORMAL;

    const MODE_NORMAL   = 0;   // Простой режим считывания INI
    const MODE_RAW      = 1;   // INI ключи-значения не будут обрабатываться
    const MODE_TYPED    = 2;   // Все значения будут преобразованы в свои сущности

    /**
     * INIStorage инициализация
     * @param $file - Путь к файлу для загрузки
     * @param bool $new - Создать новый файл если не существует.
     * @param int $mode - Режим считывания INI-файла
     */
    function __construct($file,$new=false,$mode=self::MODE_NORMAL)
    {
        if (is_readable($file)){
            $this->file = $file;
            $this->ChangeMode($mode);
            $this->load();
        } else {
            if ($new){
				touch($file);
                $this->file = $file;
                $this->ChangeMode($mode);
                $this->load();
            } else {
                new \Exception("The file is not readable.");
            }
        }
    }

    /**
     * Смена режима считывания INI-файла
     * @param $mode - Режим считывания INI-файла
     */
    function ChangeMode($mode){
        if ($mode == self::MODE_TYPED && phpversion() < 5.6){
            new \Exception("PHP version does not support this mode of operation, please change PHP version or change INI file reading mode.");
        } else {
            $this->mode = $mode;
        }
    }

    /**
     * Загрузить INI-файл
     */
    function load(){
        $this->array[0] = @parse_ini_file($this->file,false,$this->mode);
        $this->array[1] = @parse_ini_file($this->file,true,$this->mode);
        if ($this->array[0] == false){
            new \Exception("Не удалось считать INI-файл");
        }
    }

    /**
     * Получить значение ключа
     * @param $name - Имя ключа
     * @param false $section - Имя секции
     * @return mixed|null - Если ключ существует будет выведенна информация, в противном случаи NULL
     */
    function get($name,$section=false){
        if ($section){
            if (isset($this->array[1][$section][$name])){
                return $this->array[1][$section][$name];
            } else {
                return null;
            }
        } else {
            if (isset($this->array[0][$name])){
                return $this->array[0][$name];
            } else {
                return null;
            }
        }
    }
	
	/**
     * Проверяет существует ли ключ, если нет то создает его
     * При записи использовать только во всем коде или с секциями или без
     * @param $name - Ключ
     * @param $value - Значение
     * @param false $section - Секция
     *
     */
	function def($name,$value,$section=false){
		if ($section){
            if (isset($this->array[1][$section][$name])){
                return $this->array[1][$section][$name];
            } else {
                $this->array[1][$section][$name] = $value;
				return $value;
            }
        } else {
            if (isset($this->array[0][$name])){
                return $this->array[0][$name];
            } else {
				$this->array[0][$name] = $value;
                return null;
            }
        }
	}

    /**
     * Запись значение/ключ
     * При записи использовать только во всем коде или с секциями или без
     * @param $name - Ключ
     * @param $value - Значение
     * @param false $section - Секция
     *
     */
    function set($name,$value,$section=false){
        if (is_array($value)){
            new \Exception("Arrays are not supported, encode/decode information using PHP.");
        } elseif ($section){
                $this->array[1][$section][$name] = $value;
        } else {
            $this->array[0][$name] = $value;
        }
    }

    /**
     * Получить массив с ключами-значениями
     * @param false $is_selection - Вывести вместе с селекторами
     * @return array
     */
    function toArray($is_selection=false){
        if ($is_selection){
            return $this->array[1];
        } else {
            return $this->array[0];
        }
    }

    /**
     * Сохранить изменения в файл
     * @param false $section - Сохранить в виде секций
     */
    function save($section=false){
        if (!is_writable($this->file)){
            new \Exception("The file is not writable, check the permissions.");
        } elseif ($section){
            $fp = fopen($this->file, 'w');
            fwrite($fp, "; INIStorage\n");
            fwrite($fp, "; GitHub: https://github.com/pimnik98/INIStorage\n");
            fwrite($fp, '; Generation start...'."\n");
            foreach ($this->toArray(1) as $section_k => $section_v){
                if (is_array($section_v)){
                    fwrite($fp, '['.$section_k.']'."\n");
                    foreach ($section_v as $sv_k => $sv_v){
                        fwrite($fp, ($sv_k).' = "'.addslashes($sv_v)."\"\n");
                    }
                    fwrite($fp, "\n");
                } else {
                    fwrite($fp, '; Bad section '.$section_k."\n");
                }
            }
            fwrite($fp, '; Generation complete.'."\n");
            fclose($fp);
        } else {
            $fp = fopen($this->file, 'w');
            fwrite($fp, "; INIStorage\n");
            fwrite($fp, "; GitHub: https://github.com/pimnik98/INIStorage\n");
            fwrite($fp, '; Generation start...'."\n");
            foreach ($this->toArray(0) as $section_k => $section_v){
                fwrite($fp, ($section_k).' = "'.addslashes($section_v)."\"\n");
            }
            fwrite($fp, '; Generation complete.'."\n");
            fclose($fp);
        }
    }
}
?>