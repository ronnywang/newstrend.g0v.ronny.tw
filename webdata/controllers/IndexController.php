<?php

class IndexController extends Pix_Controller
{
    public function init()
    {
        $this->view->sources = array(
            0 => '合計',
            1 => '蘋果',
            2 => '中時',
            3 => '中央社',
            4 => '東森',
            5 => '自由',
            6 => '新頭殼',
            7 => 'NowNews',
            8 => '聯合',
            9 => 'TVBS',
            10 => '中廣新聞網',
            11 => '公視新聞網',
            12 => '台視',
            13 => '華視',
            14 => '民視',
//            15 => '三立',
            16 => '風傳媒',
        );

    }

    public function indexAction()
    {
    }

    public function statsAction()
    {
        list(, /*index*/, /*stats*/, $id) = explode('/', $this->getURI());
        if (!$name_list = NameList::find(intval($id))) {
            return $this->alert("找不到這個關鍵字", "/");
        }

        if (!$name_list->count_at) {
            return $this->alert("資料運算中，請稍等一下", "/");
        }

        $this->view->name_list = $name_list;
    }

    public function csvAction()
    {
        list(, /*index*/, /*api*/, $name_id) = explode('/', $this->getURI());
        if (!$name_list = NameList::find(intval($name_id))) {
            return $this->alert("找不到這個關鍵字", "/");
        }

        $sources = $this->view->sources;
        $sources[0] = 'date';
        $output = fopen('php://output', 'w');
        fputcsv($output, $sources);

        $total_news_count = array();
        foreach (NewsStat::search(1)->toArray(array('date', 'source', 'count')) as $news_stat) {
            $total_news_count[$news_stat['date'] . '-' . $news_stat['source']] = $news_stat['count'];
        }
        $hit_count = array();
        foreach (NameStat::search(array('name_id' => $name_id))->toArray(array('date', 'source', 'count')) as $name_stat) {
            if (!$hit_count[$name_stat['date']]) {
                $hit_count[$name_stat['date']] = array();
            }
            if (!$hit_count[$name_stat['date']][$name_stat['source']]) {
                $hit_count[$name_stat['date']][$name_stat['source']] = array();
            }

            $hit_count[$name_stat['date']][$name_stat['source']] = array(
                'date' => $name_stat['date'],
                'source' => $name_stat['source'],
                'count' => $name_stat['count'],
            );
        }

        foreach ($hit_count as $date => $data) {
            $ret = array($date);
            foreach ($sources as $id => $name) {
                if ($id == 0) {
                    continue;
                }
                $ret[] = intval(10000.0 * $hit_count[$date][$id]['count'] / $total_news_count[$date . '-' . $id]);
            }
            fputcsv($output, $ret);
        }
        return $this->noview();

    }

    public function apiAction()
    {
        list(, /*index*/, /*api*/, $name_id) = explode('/', $this->getURI());
        if (!$name_list = NameList::find(intval($name_id))) {
            return $this->alert("找不到這個關鍵字", "/");
        }

        $ret = new StdClass;
        $ret->sources = $this->view->sources;
        $total_news_count = array();
        foreach (NewsStat::search(1)->toArray(array('date', 'source', 'count')) as $news_stat) {
            $total_news_count[$news_stat['date'] . '-' . $news_stat['source']] = $news_stat['count'];
        }
        $ret->hit_count = array();
        foreach (NameStat::search(array('name_id' => $name_id))->toArray(array('date', 'source', 'count')) as $name_stat) {
            if (!$ret->hit_count[$name_stat['date']]) {
                $ret->hit_count[$name_stat['date']] = array();
            }
            $ret->hit_count[$name_stat['date'] . '-' . $name_stat['source']] = array(
                'date' => $name_stat['date'],
                'source' => $name_stat['source'],
                'count' => $name_stat['count'],
                'total_news' => $total_news_count[$name_stat['date'] . '-' . $name_stat['source']],
            );
        }
        $ret->hit_count = array_values($ret->hit_count);
        $ret->info = $name_list->toArray();
        return $this->json($ret);
    }

    public function addnameAction()
    {
        if (!$_POST) {
            return $this->redirect('/');
        }

        $name = trim(strval($_POST['name']));
        if (!$name) {
            return $this->alert("未輸入任何關鍵字", "/");
        }

        $disallow = '!@#$%^&*()_+[]{}\|';
        for ($i = 0; $i < strlen($disallow); $i ++) {
            if (false !== strpos($name, $disallow[$i])) {
                return $this->alert("使用到不允許的字串", "/");
            }
        }

        try {
            $n = NameList::insert(array(
                'name' => strval($_POST['name']),
                'created_at' => time(),
                'updated_at' => time(),
            ));
        } catch (Pix_Table_DuplicateException $e) {
            $n = NameList::find_by_name(strval($_POST['name']));
            $n->update(array('updated_at' => time()));
        }

        return $this->redirect('/index/stats/' . $n->id);
    }

    public function rssAction()
    {
        list(, /*index*/, /*rss*/, $name, $year_month) = explode('/', $this->getURI());

        $records = array();

        foreach (glob(getenv('DATA_PATH') . '/' . intval($year_month) . '*') as $file) {
            $cmd = "zgrep --no-filename -B 2 -A 2 " . escapeshellarg($name) . " " . escapeshellarg($file) . " | grep '{\"id\"' -A 2";
            $lines = explode("\n", trim(`$cmd`));
            for ($i = 0; $i < count($lines) - 2; $i ++) {
                $line = $lines[$i];
                if (strpos($line, '{"id":"') !== 0) {
                    continue;
                }
                if ($lines[$i + 1] == '--' or $lines[$i + 2] == '--') {
                    continue;
                }

                $record = json_decode($line);
                $i ++;
                $record->title = json_decode($lines[$i]);
                $i ++;
                $record->body = json_decode($lines[$i]);
                $records[] = $record;
            }
        }
        //return $this->json($records);
        $this->view->title = "關於 {$name} 在 {$year_month} 資料";
        $this->view->link = "http://ronny.tw";
        $this->view->description = "";
        $this->view->items = array_map(function($a){
            $ret = new StdClass;
            if ($a->source == 1) {
                $a->body = preg_replace('#<a href="[^"]*">【蘋論陣線】.*#', '', $a->body);
                $a->body = preg_replace('#<a href="[^"]*">有話要說 投稿.*#', '', $a->body);
                $a->body = preg_replace('#<a href="[^"]*">【臉團】：臉書熱門.*#', '', $a->body);
                $a->body = preg_replace('#《即時論壇》徵稿.*#', '', $a->body);
            }
            $ret->description = $a->title;
            //$ret->description = trim($a->title . $a->body);
            $ret->time = date('c', $a->created_at);
            $ret->link = $a->url;
            return $ret;
        }, $records);
    }
}
