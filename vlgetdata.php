<?php

include 'umd-api.php';

$umd_api = new umd_api;
$dept = isset($_GET['dept']) ? $_GET['dept'] : null;
$year = isset($request_vars['year']) ? $request_vars['year'] : null;
$term = isset($request_vars['term']) ? $request_vars['term'] : null;

$data = array();
$depts = 
array(/*
"AASP", "AAST", "AGNR", "AMSC", "AMST", "ANSC", "ANTH", "AOSC", "ARAB", "ARCH", "AREC", "ARHU",
"ARMY", "ARSC", "ARTH", "ARTT", "ASTR", "BCHM", "BEES", "BIOE", "BIOL", "BIOM", "BIPH", "BISI",
"BMGT", "BSCI", "BSCV", "BSGC", "BSOS", "BSST", "BUAC", "BUDT", "BUFN", "BULM", "BUMK", "BUMO",
"BUSI", "CBMG", "CCJS", "CHBE", "CHEM", "CHIN", "CHPH", "CLAS", "CLFS", "CMLT", "CMSC", "COMM",
"CONS", "CPSP", "DANC", "EALL", "ECON", "EDCI", "EDCP", "EDHD", "EDHI", "EDMS", "EDPS", "EDSP",
"EDUC", "ENAE", "ENBE", "ENCE", "ENCH", "ENCO", "ENEE", "ENES", "ENFP", "ENGL", "ENMA", "ENME",
"ENNU", "ENPM", "ENPP", "ENRE", "ENSE", "ENSP", "ENST", "ENTM", "ENTS", "EPIB", "FMSC", "FOLA",
"FREN", "GEMS", "GEOG", "GEOL", "GERM", "GREK", "GVPT", "HDCC", "HEBR", "HEIP", "HESP", "HHUM",
"HISP", "HIST", "HLSA", "HLSC", "HLTH", "HONR", "INAG", "INFM", "INST", "ISRL", "ITAL", "IVSP",
"JAPN", "JOUR", "JWST", "KNES", "KORA", "LARC", "LASC", "LATN", "LBSC", "LGBT", "LING", "MATH",
"MEES", "MIEH", "MOCB", "MUED", "MUET", "MUSC", "MUSP", "NACS", "NFSC", "PERS", "PHIL", "PHYS",*/
"PLSC", "PORT", "PSYC", "PUAF", "RDEV", "RELS", "RUSS", "SLAA", "SLAV", "SLLC", "SOCY", "SPAN",
"SPHL", "STAT", "SURV", "TDPS", "THET", "TOXI", "UMEI", "UNIV", "URSP", "USLT", "VMSC", "WMST"
);

foreach ($depts as $dept) {
  $dept_obj = $umd_api->get_schedule($year, $term, $dept);
  $data[$dept] = array();

  foreach ($dept_obj->courses as $course) {
    foreach ($course->sections as $section) {
      if (!isset($data[$dept][$section->number]))
        $data[$dept][$section->number] = 1;
      else
        $data[$dept][$section->number] ++;
    }
  }
}

echo json_encode($data); //, 'application/json');


/*
freqs = [{},{},{},{}];
for (var dept in nums) {
  for (var sec in nums[dept]) {
    for (var a = 0; a < 4; a ++) {
      var c = sec[a];
      if (!(c in freqs[a]))
        freqs[a][c] = 0;
      freqs[a][c] += nums[dept][sec];
    }
  }
}
*/
?>