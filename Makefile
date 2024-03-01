image=yiimp
version=2024.03r01

build:
	podman build --tag $(image) --target image-prod -f Dockerfile.yiimp 
build-devel:
	podman build --tag $(image) --target image-devel -f Dockerfile.yiimp 

push:
	podman push $(image) ghcr.io/tpfuemp/$(image):$(version)

run:
	podman rm -i $(image) && podman run --name=$(image) --network=host -v ./config:/etc/yiimp -v ./log:/var/log/apache2 $(image)
run-devel:
	podman rm -i $(image) && podman run --name=$(image) --network=host -v ./config:/etc/yiimp -v ./yiimp/web:/var/www/ -v ./log:/var/log/apache2 $(image)
