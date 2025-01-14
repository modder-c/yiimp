# change image name
image_name=pirate
# version tag
version=v5.9.0
# local storage for container dumps
container_savepath=${CONTAINER_SAVEPATH}

# change STRATUM_UPDATEPASS to your own password
STRATUM_UPDATEPASS=tu8tu5

# coin paths
walletuserid=2000
walletgroupid=2000
user_path=/home/walletuser
wallet_directory=.pirate
wallet_daemon=pirated -ac_name=PIRATE -ac_supply=0 -ac_reward=25600000000 -ac_halving=77777 -ac_private=1 -addnode=136.243.102.225

#change container registry path and credentials
registry=ghcr.io/<username>
registry_username=xxx
registry_password=xxx

gitclone:
	git clone https://github.com/PirateNetwork/pirate

build:
	podman build --tag ${image_name} --build-arg STRATUM_UPDATEPASS=${STRATUM_UPDATEPASS} --build-arg walletuserid=${walletuserid} --build-arg walletgroupid=${walletgroupid} -f Dockerfile
push:
	podman push ${image_name} ${registry}/${image_name}:${version}
save:
	podman save -o ${container_savepath}container-${image_name}-${version}.tar ${image_name}
load:
	podman load -i ${container_savepath}container-${image_name}-${version}.tar
load-gz:
	podman load -i ${container_savepath}container-${image_name}-${version}.tar.gz
run:
	podman rm -i ${image_name} && podman run --name=${image_name} -dt --network=host --log-driver=journald -v /dev/log:/dev/log -v /home/walletuser/.zcash-params:/home/walletuser/.zcash-params -v ${user_path}/${wallet_directory}:${user_path}/.komodo ${image_name} ${wallet_daemon} -maxconnections=8
run-reindex:
	podman rm -i ${image_name} && podman run --name=${image_name} -dt --network=host --log-driver=journald -v /dev/log:/dev/log -v /home/walletuser/.zcash-params:/home/walletuser/.zcash-params -v ${user_path}/${wallet_directory}:${user_path}/.komodo ${image_name} ${wallet_daemon} -maxconnections=8 -reindex
