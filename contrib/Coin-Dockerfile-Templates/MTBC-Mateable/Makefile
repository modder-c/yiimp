# change image name
image_name=mateablecoin
#version tag
version=24.02R01

# change STRATUM_UPDATEPASS to your own password
STRATUM_UPDATEPASS=tu8tu5

# coin paths
walletuserid=2000
walletgroupid=2000
user_path=/home/walletuser
wallet_directory=.mateable
wallet_daemon=mateabled

#change container registry path and credentials
registry=ghcr.io/<username>
registry_username=xxx
registry_password=xxx

gitclone:
	git clone https://github.com/mateable/mateablecoin-24.x

build:
	podman build --tag ${image_name} --build-arg STRATUM_UPDATEPASS=${STRATUM_UPDATEPASS} --build-arg walletuserid=${walletuserid} --build-arg walletgroupid=${walletgroupid} -f Dockerfile
push:
	podman push ${image_name} ${registry}/${image_name}:${version}
run:
	podman rm -i ${image_name} && podman run --name=${image_name} -dt --network=host --log-driver=journald -v /dev/log:/dev/log -v ${user_path}/${wallet_directory}:${user_path}/${wallet_directory} ${image_name} ${wallet_daemon} -maxconnections=8
run-reindex:
	podman rm -i ${image_name} && podman run --name=${image_name} -dt --network=host --log-driver=journald -v /dev/log:/dev/log -v ${user_path}/${wallet_directory}:${user_path}/${wallet_directory} ${image_name} ${wallet_daemon} -maxconnections=8 -reindex
