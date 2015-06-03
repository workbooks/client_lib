import subprocess as sub

command = "ruby simple_example.rb"
p = sub.Popen(command, shell=True, stdout=sub.PIPE)
output, errors = p.communicate()

print output
