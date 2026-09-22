<?php
namespace App\Models;

use App\Core\Model;

class RatingSetting extends Model {

    public function getAll() {
        $this->db->query("SELECT * FROM rating_settings ORDER BY sort_order ASC, id ASC");
        return $this->db->fetchAll();
    }

    public function getOrdered() {
        return $this->getAll();
    }

    public function replaceAll($rows) {
        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM rating_settings");
            $this->db->execute();
            foreach($rows as $i=>$r){
                $this->db->query("INSERT INTO rating_settings (label,min_score,max_score,color,sort_order) VALUES (:l,:min,:max,:c,:o)");
                $this->db->bind(':l', trim($r['label']));
                $this->db->bind(':min', (float)$r['min_score']);
                $this->db->bind(':max', (float)$r['max_score']);
                $this->db->bind(':c', $r['color']);
                $this->db->bind(':o', $i+1);
                $this->db->execute();
            }
            $this->db->commit();
            return true;
        } catch(\Exception $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    public static function classify($score, $settings=null) {
        if($settings===null){
            $db=\App\Core\Database::getInstance();
            $db->query("SELECT * FROM rating_settings ORDER BY sort_order ASC");
            $settings=$db->fetchAll();
        }
        foreach($settings as $s){
            if($score >= (float)$s->min_score && $score <= (float)$s->max_score) return $s;
        }
        // fallback by order
        if($settings){
            usort($settings, fn($a,$b)=> $b->max_score <=> $a->max_score);
            foreach($settings as $s) if($score >= $s->min_score) return $s;
            return end($settings);
        }
        return (object)['label'=>'غير مصنف','color'=>'#64748b'];
    }

    public static function buildDistributionCase($settings) {
        if(!$settings) return "SUM(CASE WHEN 1=0 THEN 1 ELSE 0 END) as dummy";
        $cases=[];
        foreach($settings as $i=>$s){
            $cases[]="SUM(CASE WHEN final_score >= ".(float)$s->min_score." AND final_score <= ".(float)$s->max_score." THEN 1 ELSE 0 END) as cat_$i";
        }
        return implode(", ", $cases);
    }
}
