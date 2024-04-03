# change image name
image_name=egulden
#version tag
version=24.04R01

# change STRATUM_UPDATEPASS to your own password
STRATUM_UPDATEPASS=tu8tu5

# coin paths
walletuserid=2000
walletgroupid=2000
user_path=/home/walletuser
wallet_directory=.egulden
wallet_daemon=eguldend

#change container registry path and credentials
registry=ghcr.io/<username>
registry_username=xxx
registry_password=xxx

gitclone:
	git clone https://github.com/Electronic-Gulden-Foundation/egulden

build:
	podman build --tag ${image_name} --build-arg STRATUM_UPDATEPASS=${STRATUM_UPDATEPASS} --build-arg walletuserid=${walletuserid} --build-arg walletgroupid=${walletgroupid} -f Dockerfile
push:
	podman push ${image_name} ${registry}/${image_name}:${version}
run:
	podman rm -i ${image_name} && podman run --name=${image_name} -dt --network=host --log-driver=journald -v /dev/log:/dev/log -v ${user_path}/${wallet_directory}:${user_path}/${wallet_directory} ${image_name} ${wallet_daemon} -maxconnections=8
run-reindex:
	podman rm -i ${image_name} && podman run --name=${image_name} -dt --network=host --log-driver=journald -v /dev/log:/dev/log -v ${user_path}/${wallet_directory}:${user_path}/${wallet_directory} ${image_name} ${wallet_daemon} -maxconnections=8 -reindex
