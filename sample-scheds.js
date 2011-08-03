var sample_schedules = [
  { 'name': 'Ophir',     'id': 'Ophir',  'sched': 'MATH241 0412, PHYS272 0101, PHYS174 0103, CPSP118D 0102, CMSC132 0102, LING200 0101' },
  { 'name': 'Bae',       'id': 'Bae',    'sched': 'MATH340 0101, PHIL209I 0102, PHYS174 0108, PHYS272H 0101, GEMS100 0401, ECON200 2305' },
  { 'name': 'Nitay',     'id': 'Nitay',  'sched': 'MATH241 0211, ASTR120 0101, SOCY100 0501, PHYS272H 0101, CPSP118D 0102, PHYS174 0101' },
  { 'name': 'James',     'id': 'James',  'sched': 'ARCH170 0107, HIST284 0102, BMGT230B 0205, BMGT110F FP06, BMGT220 0302, CPSP118E 0101' },
  { 'name': 'Alex Yee',  'id': 'AlexY',  'sched': 'CMSC132 0302, ENES100 0201, HDCC105 0101, HONR249P 0101, MATH340 0201' },
  { 'name': 'Alex C-G',  'id': 'AlexCG', 'sched': 'CPSP118D 0101, ENGL278T 0101, MATH340 0201, PHYS161 0302, CHEM135 3227, ENES100 0101' },
  { 'name': 'Ori',       'id': 'Ori',    'sched': 'CHEM135 3148, ENES102H 0101, HONR100 0102, HONR208F 0101, MATH141 0322' },
];
sample_schedules.sort(function(a, b) { return a.name.localeCompare(b.name); });