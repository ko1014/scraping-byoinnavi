<?php
define("DOMAIN", "https://byoinnavi.jp");
require_once('phpQuery-onefile.php');
class Scraping
{
    private $url;
    private $file = "data.txt";
    private $log = "./tmp.log";
    private $data = array();
    const count = 15;
    function __construct($url)
    {
        $this->url = $url;
    }

    public function index()
    {
        // page数カウント
        $dom = $this->getDom($this->url); 
        $number = (int) str_replace(',','',$dom[".key:eq(0)"]->text());
        $pages = $number / self::count;
        $pages = ceil($pages);
        /*
        $csv_header = array(
            'clinic_name',
            'clinic_homepage',
            'clinic_cate',
            'clinic_tel',
            'clinic_address',
            'clinic_station',
            'clinic_holiday',
            'outer',
            'director'
        );
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=data.csv');
        $stream = fopen('php://output', 'w');
        fputcsv($stream, $csv_header);
        //*/
        for ($i = 0; $i < $pages; $i++) {
            $dom = (in_array($i, [0, 1])) ? $this->getDom($this->url) : $this->getDom($this->url. "?p=". $i);
            //$this->putCsv($dom, $stream);
            $this->putText($dom);
            error_log($i. PHP_EOL, 3, $this->log);
            unset($dom);
        }
    }

    private function getOuter($text)
    {
        return !empty($text) ? "有" : "無";
    }

    private function getDirector($directorUrl)
    {
        $dom = $this->getDom($directorUrl);
        return $dom->find('.corp-info-ext__director-name')->text();
    }

    private function getDom($url)
    {
        $html = file_get_contents($url);
        if (empty($html)) {
            return;
        }
        // dom作成
        return phpQuery::newDocument($html);
    }

    private function mbConvertTrim($text)
    {
        //mb_convert_variables('SJIS-win', 'UTF-8', $text);
        $text = $this->lineFeedTrim($text);
        return trim($text, " \t\n\r\0\x0B");
    }

    private function putCsv($dom, $stream)
    {
        foreach ($dom[".clinic"] as $partDom) {
            $data = $this->scraping($partDom);
            fputcsv($stream, $data);
        }
    }

    private function lineFeedTrim($str)
    {
        return str_replace(array(" ","\t","\r\n","\n","\r"), "", $str);
    }

    private function putText($dom)
    {
        foreach ($dom[".clinic"] as $partDom) {
            $text = $this->findText(pq($partDom), ".clinic_name"). "\t";
            $text .= $this->findHref(pq($partDom), ".clinic_url a"). "\t";
            $text .= $this->findText(pq($partDom), ".clinic_cate"). "\t";
            $text .= $this->findText(pq($partDom), ".clinic_tel"). "\t";
            $text .= $this->findText(pq($partDom), ".clinic_address"). "\t";
            $text .= $this->findText(pq($partDom), ".clinic_rail_station"). "\t";
            $text .= $this->findText(pq($partDom), ".clinic_list_hour_holiday"). "\t";
            $text .= $this->mbConvertTrim($this->getOuter($this->findText(pq($partDom), ".clinic_bsa3_text2"))). "\t";
            $text .= $this->mbConvertTrim($this->getDirector(DOMAIN. $this->findHref(pq($partDom), ".clinic_name a"))). "\n";
            //mb_convert_variables('UTF-8', 'SJIS-win', $text);
            //trim($text, " \t\n\r\0\x0B");
            file_put_contents($this->file, $text, FILE_APPEND);
            unset($text);
        }
    }

    private function scraping($dom)
    {
        $data = array();
        $data[] = $this->findText(pq($dom), ".clinic_name");
        $data[] = $this->findHref(pq($dom), ".clinic_url a");
        $data[] = $this->findText(pq($dom), ".clinic_cate");
        $data[] = $this->findText(pq($dom), ".clinic_tel");
        $data[] = $this->findText(pq($dom), ".clinic_address");
        $data[] = $this->findText(pq($dom), ".clinic_rail_station");
        $data[] = $this->findText(pq($dom), ".clinic_list_hour_holiday");
        $data[] = $this->mbConvertTrim($this->getOuter($this->findText(pq($dom), ".clinic_bsa3_text2")));
        $data[] = $this->mbConvertTrim($this->getDirector(DOMAIN. $this->findHref(pq($dom), ".clinic_name a")));
        return $data;
    }

    private function findText($dom, $cluster)
    {
        return $this->mbConvertTrim($dom->find($cluster)->text());
    }

    private function findHref($dom, $cluster)
    {
        $text = $dom->find($cluster)->attr("href");
        return trim($text, " \t\n\r\0\x0B");
    }
}
$directory = ["kanagawa"];
$scraping = new Scraping(DOMAIN. "/". $directory[0]);
$scraping->index();
