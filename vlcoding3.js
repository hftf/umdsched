var chars = {
  14: "0123456789CGJP",
  26: "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
  36: "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ",
  64: "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_=",
}
var regexes = {
  14: /^[CGJP\d]+$/,
  26: /^[A-Z]+$/,
  36: /^[A-Z\d]+$/,
  64: /^[A-Za-z\d_=]+$/,
  108: new RegExp("[" + chars[108] + "]")
}
var depts = [
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
"MEES", "MIEH", "MOCB", "MUED", "MUET", "MUSC", "MUSP", "NACS", "NFSC", "PERS", "PHIL", "PHYS",
"PLSC", "PORT", "PSYC", "PUAF", "RDEV", "RELS", "RUSS", "SLAA", "SLAV", "SLLC", "SOCY", "SPAN",
"SPHL", "STAT", "SURV", "TDPS", "THET", "TOXI", "UMEI", "UNIV", "URSP", "USLT", "VMSC", "WMST"
];
var version = "0-", depts_length = depts.length, add_depts = 0, secs_length = 1e4, add_secs = 0;

var multiplier_order = [ 'sec', 'course_123', 'course_4', 'dept' ], multipliers_length = multiplier_order.length;
var multiplier_alt_flags = { dept: 1, course_123: 0, course_4: 0 /*2*/, sec: 4 };
var multipliers = {                       /*26^4*/
  dept:       [ depts_length, 456976 + add_depts * depts_length ],
  course_123: [ 1e3, NaN ],
  course_4:   [ 27, NaN ],
  sec:        [ secs_length,  36*36*36*14 + add_secs * secs_length ]
}


function secs2bigInt(sections) {
  return sections.split(", ").map(sec2bigInt);//.join("|");
}
  
function sec2bigInt(section, version, debug) {
  // (0) Setup
  var encoding_type = 0;
  var multiplicands  = { dept: 0, course_123: 0, course_4: 0, sec: 0 };
  var multiplier_map = { dept: 0, course_123: 0, course_4: 0, sec: 0 };
  var product = [0], cum_multiplier = [1];
  
  // (1) Split components into array
  var sp1 = section.split(/\s/);
  var sp2 = [sp1[0].substring(0,4), sp1[0].substring(4,7), sp1[0].substring(7), sp1[1]];
  
  // (2a) Determine how to encode sec
  try { multiplicands.sec = encode_sec(sp2[3]); }
  catch (e) {
    if (e !== ~~e) throw e; // Rethrow in the case that encode_sec actually throws an error
    encoding_type |= multiplier_alt_flags.sec; multiplicands.sec = e; multiplier_map.sec = 1;
  }
  
  // (2b) Encode course_123
  multiplicands.course_123 = parseInt(sp2[1], 10);
  
  // (2c) Determine whether to encode course_4
  try { multiplicands.course_4 = encode_course_4(sp2[2]); }
  catch (e) {
    if (e !== ~~e) throw e; // Rethrow in the case that encode_course_4 actually throws an error
    encoding_type |= multiplier_alt_flags.course_4; multiplicands.course_4 = e;
  }
  
  // (2d) Determine how to encode dept
  try { multiplicands.dept = encode_dept(sp2[0]); }
  catch (e) {
    encoding_type |= multiplier_alt_flags.dept; multiplicands.dept = e; multiplier_map.dept = 1;
  }
  
  // (3) Multiply and sum for each component
  for (var i = 0; i < multipliers_length; i ++) {
    var component = multiplier_order[i];
    product = add(product, mult(cum_multiplier, int2bigInt(multiplicands[component], 54)));
    cum_multiplier = mult(cum_multiplier, int2bigInt(multipliers[component][multiplier_map[component]], 54));
  }
  
  // (4) Build the base 64-encoded string, with requisite prefix if applicable
  var encoded = bigInt2str(product, 64);
  var encoded_str = ''; //version;
  // If not 0 or 2 (-magic number-), prepend the encoding type
  if (encoding_type & ~multiplier_alt_flags.course_4)
    encoded_str = encoding_type + '-';
  encoded_str += encoded;
  
  return encoded_str;
}

function decode_dcs_new(encoded, debug) {
  var encoding_type = 0;
  if (encoded.indexOf('-') > -1) {
    encoding_type = +encoded.charAt(0);
    encoded = encoded.substring(2);
  }
  
  var chunk = str2bigInt(encoded, 64);
  
  var multiplicands = {}, multiplier_map = {};
  for (var i = 0; i < multipliers_length; i ++) {
    var component = multiplier_order[i];
    var component_flag = multiplier_alt_flags[component];
    multiplier_map[component] = +!!(component_flag & encoding_type);
    var multiplier = multipliers[component][multiplier_map[component]];
    
    multiplicands[component] = divInt_(chunk, multiplier);
  }
  
  if (!isZero(chunk))
    throw "Quotient > 0 somewhere";
  
  var sec_str = construct_(multiplicands, multiplier_map);
  
  return sec_str;
}


var memoize;
(function() {
  var cache = {};
  function cacheAdd(sec) {
    var encoding_function = sec2bigInt;
  
    var encoded = encoding_function(sec, version, false);
    
    // Check if already memoized
    if (cache.hasOwnProperty(sec)) {
      // console.log('Section found in cache (' + (++ cache[sec].count) + '). Checking result of encoding...');
      if (encoded !== cache[sec]) // .encoded)
        throw 'Result does not match cache.';
      // else
      //   console.log('Result matches cache.');
    }
    else {
      // console.log('Section not found in cache. Adding to cache...');
      cache[sec] = encoded; // { encoded: encoded, count: 1 };
    }
  }

  memoize = function(inputs) {
    inputs.forEach(cacheAdd);
    return cache;
  };
})();
function testCache(cache) {
  var decoding_function = decode_dcs_new;
  var passed = 0, total = 0;
  
  Object.keys(cache).forEach(function(sec) {
    var decoded;
    ++ total;
    try {
      decoded = decoding_function(cache[sec], false);
      if (decoded !== sec)
        throw 'Expected ' + sec + ', but got ' + decoded + '.';
      else
        ++ passed;
    }
    catch (e) {
      console.log('Exception caught while trying to decode ' + cache[sec] + ' into ' + sec + ': ', e);
    }
  });
  
  return { passed: passed, total: total };
}

// Flatten all sections into array: Reduce two layers of arrays, while also splitting schedules
var inputs = [sample_schedules_201108, sample_schedules_201201, sample_schedules].reduce(
  function(p,c) { return p.concat(c.reduce(
    function(p,c){ return p.concat(c.sched.split(', ')) }
  ,[])) }
, []);



function construct(dept, course_123, course_4, sec) {
  return construct_({ dept: dept, course_123: course_123, course_4: course_4, sec: sec }, { dept: 0, course_123: 0, course_4: 0, sec: 0 });
}
function construct_(multiplicands, multiplier_map) {
  return decode_dept(multiplicands.dept, multiplier_map.dept) + pad('' + multiplicands.course_123, 3) +
         decode_course_4(multiplicands.course_4, multiplier_map.course_4) + ' ' + decode_sec(multiplicands.sec, multiplier_map.sec);
}
function pad(s, l, p) { if (p === undefined) p = '0'; return s.length >= l ? s : pad(p + s, l, p); }
function pb(v, b) { return b << Math.floor(1 + Math.log(v)/Math.LN2) | v; }
function itoa(input, base, f) {
  if (input !== Math.floor(input) || (base != 64 && input < 0)) {
    throw "Invalid input (" + input + "): must be non-negative integer.";
    return null;
  }
    
  var quotient = input, result = [];
  do {
    if (f)
      result.push(f(quotient % base));
    else
      result.push(chars[base][quotient % base]);
    quotient = Math.floor(quotient / base);
  } while (quotient);
  return result.reverse().join("");
}

function atoi(input, base) {
  if (!regexes[base].test(input)) {
    throw "Input '" + input + "' is invalid in base " + base + ".";
    return null;
  }
  var j = input.length, i = 0, result = 0;
  while (j--) {
    result += chars[base].indexOf(input[j]) * Math.pow(base, i++);
  }
  return result;
}

function encode_dept(dept_str) {
  var dept = depts.indexOf(dept_str);
  if (dept > -1)
    return dept;
  else
    throw atoi(dept_str, 26) + add_depts * depts_length;
}
function decode_dept(dept, flag) {
  dept -= add_depts * depts_length;
  if (flag) {
    if (dept > -1 && dept < multipliers.dept[flag])
      return pad(itoa(dept, 26), 4, 'A'); //chars[14][0]
  }
  else {
    if (dept < depts_length && dept > -1)
      return depts[dept];
  }
  throw "Invalid dept_str";
}

function encode_course_4(course_4_str) {
  if (course_4_str === "")
    return 0;
    
  var course_4 = chars[26].indexOf(course_4_str);
  if (course_4 > -1 && course_4_str.length === 1)
    throw course_4 + 1;
  else
    throw "Invalid course_4";
}
function decode_course_4(course_4, flag) {
  if (course_4 === 0)
    return "";
  if (course_4 > -1 && course_4 < multipliers.course_4[flag])
    return chars[26].charAt(course_4 - 1);
  throw "Invalid course_4";
}

function encode_sec(sec_str) {
  if (!/[A-Z]/.test(sec_str))
    return parseInt(sec_str, 10);

  throw atoi(sec_str.substring(0,3), 36) * 14 + atoi(sec_str[3], 14) + add_secs * secs_length;
}
function decode_sec(sec, flag) {
  sec -= add_secs * length;
  if (flag) {
    if (sec > -1 && sec < multipliers.sec[flag])
      return pad('' + itoa(Math.floor(sec / 14), 36) + itoa(sec % 14, 14), 4);
  }
  else
    return pad('' + sec, 4);
  throw "Invalid sec";
}

function rand36() { return(itoa(Math.floor(Math.random()*Math.pow(64,6)),64) ); }
function rands() { return construct(~~(Math.random()*depts_length),~~(Math.random()*1000),~~(Math.random()*27),~~(Math.random()*secs_length));}
function prof(n) {
  var failed = [];
  for (var ni = 0; ni < n; ni ++) {
    var r = rands();
    var s=decode_dcs_new(sec2bigInt(r));
    if (r !== s)
      failed.push(r);
  }
  return failed;
}