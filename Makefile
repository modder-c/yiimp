image=yiimp
version=2024.03r01

build:
	git submodule init && git submodule update
	podman build --tag $(image) --target image-prod -f Dockerfile.yiimp 
build-devel:
	git submodule init && git submodule update
	podman build --tag $(image) --target image-devel -f Dockerfile.yiimp 

push:
	podman push $(image) ghcr.io/tpfuemp/$(image):$(version)

run:
	podman rm -i $(image) && podman run --name=$(image) --network=host -v ./config:/etc/yiimp -v ./log:/var/log/apache2 -v ./log:/var/log/yiimp -v ./log:/var/www/yaamp/runtime -v ./config/supervisord.conf:/etc/supervisor/conf.d/supervisord.conf $(image)
run-devel:
	podman rm -i $(image) && podman run --name=$(image) --network=host -v ./config:/etc/yiimp -v ./yiimp/web:/var/www/ -v ./log:/var/log/apache2 -v ./log:/var/log/yiimp -v ./log:/var/www/yaamp/runtime -v ./config/supervisord.conf:/etc/supervisor/conf.d/supervisord.conf $(image) /usr/bin/supervisord
