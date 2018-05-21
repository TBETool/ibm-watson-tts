<?php
namespace TBETool;
/**
 * Created by PhpStorm.
 * User: anuj
 * Date: 21/5/18
 * Time: 11:14 AM
 */

use Exception;

/**
 * Class WatsonTts
 * @package TBETool
 */
class WatsonTts
{
    private $WATSON_USERNAME;
    private $WATSON_PASSWORD;
    private $WATSON_URL;
    private $valid_audio_formats = ['ogg', 'wav'];
    private $audio_format = 'wav';
    private $valid_language = ['en'];
    private $language = 'en';
    private $valid_voices = ['US_MichaelVoice'];
    private $voice = 'US_MichaelVoice';
    private $output_path;
    private $output_file_name;
    private $output_file_path;
    private $text;

    /**
     * WatsonTts constructor.
     * set watson username, password and watson_url
     *
     * @param $watson_username
     * @param $watson_password
     * @param $watson_url
     */
    public function __construct($watson_username, $watson_password, $watson_url = null)
    {
        $this->WATSON_USERNAME = $watson_username;
        $this->WATSON_PASSWORD = $watson_password;

        if (!empty($watson_url)) {
            $this->WATSON_URL = $watson_url;
        }
    }

    /**
     * set watson url
     *
     * @param $watson_url
     * @throws Exception
     */
    public function setWatsonUrl($watson_url)
    {
        if (empty($watson_url))
            throw new Exception('Watson URL not provided');

        $this->WATSON_URL = $watson_url;
    }

    /**
     * set audio format,
     * default: wav
     *
     * @param $format
     * @throws Exception
     */
    public function setAudioFormat($format)
    {
        if (empty($format))
            throw new Exception('Audio format string is empty');

        if (!in_array($format, $this->valid_audio_formats))
            throw new Exception(
                'Not a valid audio format. Allowed formats: '.implode(' ', $this->valid_audio_formats)
            );

        $this->audio_format = $format;
    }

    /**
     * set language of audio,
     * default: en
     *
     * @param $language
     * @throws Exception
     */
    public function setLanguage($language)
    {
        if (empty($language))
            throw new Exception('Language string is empty');

        if (!in_array($language, $this->valid_language))
            throw new Exception(
                'Not a valid language provided. Allowed languages: '.implode(' ', $this->valid_language)
            );

        $this->language = $language;
    }

    /**
     * set voice,
     * default:
     *
     * @param $voice
     * @throws Exception
     */
    public function setVoice($voice)
    {
        if (empty($voice))
            throw new Exception('Voice string is empty');

        if (!in_array($voice, $this->valid_voices))
            throw new Exception(
                'Not a valid voice provided. Allowed voices: '.implode(' ',$this->valid_voices)
            );

        $this->voice = $voice;
    }

    /**
     * set output path
     *
     * @param $output_path
     * @throws Exception
     */
    public function setOutputPath($output_path)
    {
        if (empty($output_path))
            throw new Exception('Output path is empty');

        if (!$this->checkAndCreateDirectory($output_path))
            throw new Exception('Unable to create output directory');

        $this->output_path = $output_path;
    }

    /**
     * check for if output_path is directory,
     * else create path,
     *
     * @param $output_path
     * @return bool
     */
    private function checkAndCreateDirectory($output_path)
    {
        if (is_dir($output_path))
            return true;

        if (mkdir($output_path, 0777, true))
            return true;

        return false;
    }

    /**
     * text to speech serializer
     *
     * @param $text
     * @param null $format
     * @param null $language
     * @param null $voice
     * @throws Exception
     */
    public function tts($text, $format = null, $language = null, $voice = null)
    {
        if (empty($text))
            throw new Exception('No text string provided');

        $this->text = $text;

        if (empty($this->output_path))
            throw new Exception('Output path is not set. Please set output path by passing absolute path string to setOutputPath()');

        if (!empty($format))
            $this->setAudioFormat($format);

        if (!empty($language))
            $this->setLanguage($language);

        if (!empty($voice))
            $this->setVoice($voice);

        $this->prepareOutputFile();

        $this->processWatsonTtsCurl();
    }

    /**
     * prepare output file and name
     */
    private function prepareOutputFile()
    {
        $file_name = time().'_'.str_shuffle(time()).'.'.$this->audio_format;

        $this->output_file_name = $file_name;

        $this->output_file_path = rtrim($this->output_path, '/').'/'.$this->output_file_name;
    }

    /**
     * process watson curl script
     *
     * @throws Exception
     */
    private function processWatsonTtsCurl()
    {
        $text_data = [
            'text' => $this->text
        ];

        $text_json = json_encode($text_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->WATSON_URL);
        curl_setopt($ch, CURLOPT_USERPWD, $this->WATSON_USERNAME.':'.$this->WATSON_PASSWORD);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: audio/'.$this->audio_format,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $text_json);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Error with curl response: '.curl_error($ch));
        }
        curl_close($ch);

        print_r($result);
    }

}