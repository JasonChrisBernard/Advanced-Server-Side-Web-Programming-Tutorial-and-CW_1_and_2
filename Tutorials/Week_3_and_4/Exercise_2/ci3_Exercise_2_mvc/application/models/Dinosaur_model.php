<?php defined('BASEPATH') or exit('No direct script access allowed');
    class Dinosaur_model extends CI_Model{
        private $periods = array(
        'Triassic' => array(
            'period' => 'Triassic',
            'time' => '237–201 mya',
            'land_animals' => 'Archosaurs and therapsids',
            'marine_animals' => 'Plesiosaurs, ichthyosaurs, and fish',
            'avian_animals' => 'No major avian animals listed',
            'plant_life' => 'Cycads, ferns, Gingko-like trees, and seed plants'
        ),

        'Jurassic' => array(
            'period' => 'Jurassic',
            'time' => '201–145 mya',
            'land_animals' => 'Dinosaurs such as sauropods and therapods, early mammals, and feathered dinosaurs',
            'marine_animals' => 'Plesiosaurs, fish, squid, and marine reptiles',
            'avian_animals' => 'Pterosaurs and flying insects',
            'plant_life' => 'Ferns, conifers, cycads, club mosses, horsetail, and flowering plants'
        ),

        'Cretaceous' => array(
            'period' => 'Cretaceous',
            'time' => '145–66 mya',
            'land_animals' => 'Dinosaurs such as sauropods, therapods, raptors, hadrosaurs, ceratopsians, and small mammals',
            'marine_animals' => 'Plesiosaurs, pliosaurs, mosasaurs, sharks, fish, squid, and marine reptiles',
            'avian_animals' => 'Pterosaurs, flying insects, and feathered birds',
            'plant_life' => 'Huge expansion of flowering plants'
        )
    );

    public function get_all_periods(){
        return array_keys($this -> periods);
    }

    public function get_period_info($period){
        if(array_key_exists($period, $this->periods))   {
            return $this -> periods[$period];
        }
    }
    }


?>