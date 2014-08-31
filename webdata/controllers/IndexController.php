<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
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
}
