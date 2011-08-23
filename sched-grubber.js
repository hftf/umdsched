function parseInput(input) {
    var output = input.split(/\n\s*/)
                      .map(function(a) { return a.split(/  +\s*|\t\s*/); })
                      .filter(function(a) { return a.length == 4 || a.length == 5; })
                      .map(function(a) { return a.slice(0, 2).join(' '); })
                      .filter(function(s) { return !(s == '' || s.substring(0, 6) == 'Course') })
                      .join(', ');
    return output;
}

function parsePrompt() {
    prompt('Copy the text below:', parseInput(document.getElementById('sched-input').value));
}