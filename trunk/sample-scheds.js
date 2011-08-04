var sample_schedules = [
  { 'name': 'Ophir',     'id': 'Ophir',   'sched': 'MATH241 0412, PHYS272 0101, PHYS174 0103, CPSP118D 0102, CMSC132 0102, LING200 0101' },
  { 'name': 'Bae',       'id': 'Bae',     'sched': 'MATH340 0101, PHIL209I 0102, PHYS174 0108, PHYS272H 0101, GEMS100 0401, ECON200 2305' },
  { 'name': 'Nitay',     'id': 'Nitay',   'sched': 'MATH241 0211, ASTR120 0101, SOCY100 0501, PHYS272H 0101, CPSP118D 0102, PHYS174 0101' },
  { 'name': 'James',     'id': 'JamesL',  'sched': 'ARCH170 0107, HIST284 0102, BMGT230B 0205, BMGT110F FP06, BMGT220 0302, CPSP118E 0101' },
  { 'name': 'Austin',    'id': 'AustinH', 'sched': 'CHEM135 3345, PHYS270 0302, PHYS271 0106, MATH241 0141, HONR269B 0101, HONR100 0101' },
  { 'name': 'Alex Yee',  'id': 'AlexY',   'sched': 'CMSC132 0302, ENES100 0201, HDCC105 0101, HONR249P 0101, MATH340 0201' },
  { 'name': 'Alex C-G',  'id': 'AlexCG',  'sched': 'CPSP118D 0101, ENGL278T 0101, MATH340 0201, PHYS161 0302, CHEM135 3227, ENES100 0101' },
  { 'name': 'Ori',       'id': 'Ori',     'sched': 'CHEM135 3148, ENES102H 0101, HONR100 0102, HONR208F 0101, MATH141 0322' },
  { 'name': 'Andrew R-S','id': 'AndrewRS','sched': 'ECON200 2107, HDCC105 0101, HONR268E 0101, MATH340 0201' },
  { 'name': 'Sam',       'id': 'SamG',    'sched': 'CHEM135 3127, CMLT245 0401, CPSP118D 0101, ENES100 0601, MATH141 0222' },
  { 'name': 'Dennis',    'id': 'DennisTr','sched': 'BMGT110F FP09, BMGT220 0205, BMGT230B 0207, CPSP118T 0101, ENGL245 0501, ENGL278T 0101' },
  { 'name': 'Tingrui',   'id': 'Tingrui', 'sched': 'CHEM231 5336, CHEM232 5121, HLSC207 0101, MATH241 0411, HONR100 0307, PHYS174 0108' },
  { 'name': 'Ozzie',     'id': 'OzzieF',  'sched': 'GERM203 0101, MUSC205 0104, HDCC208B 0101, CMSC330 0103, CMSC351 0201' },
];
sample_schedules.sort(function(a, b) { return a.name.localeCompare(b.name); });