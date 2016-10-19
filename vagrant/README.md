##Development

* debian

run `vagrant up frontend` from debian directory

guest ip is 192.168.242.52
login/pw is vagrant/vagrant


* fedora

run `vagrant up frontend` from fedora directory
run `vagrant plugin install vagrant-vbguest` to install VirtualBox guest additions
    (needed for working sync of handler/Guidepost folder)

command `vagrant ssh frontend` gives you command line of that virtual machine.
