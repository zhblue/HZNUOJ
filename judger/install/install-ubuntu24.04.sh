#!/bin/bash
echo ""
echo " ██      ██ ████████ ████     ██ ██     ██   ███████        ██"
echo "░██     ░██░░░░░░██ ░██░██   ░██░██    ░██  ██░░░░░██      ░██"
echo "░██     ░██     ██  ░██░░██  ░██░██    ░██ ██     ░░██     ░██"
echo "░██████████    ██   ░██ ░░██ ░██░██    ░██░██      ░██     ░██"
echo "░██░░░░░░██   ██    ░██  ░░██░██░██    ░██░██      ░██     ░██"
echo "░██     ░██  ██     ░██   ░░████░██    ░██░░██     ██  ██  ░██"
echo "░██     ░██ ████████░██    ░░███░░███████  ░░███████  ░░█████ "
echo "░░      ░░ ░░░░░░░░ ░░      ░░░  ░░░░░░░    ░░░░░░░    ░░░░░  "
echo ""
apt-get update
for pkg in "libmysqlclient-dev libmysql++-dev mysql-server subversion"
do
    while ! apt-get install -y $pkg 
    do
        echo "Network fail, retry... you might want to change another apt source for install"
    done
done
PHP_VER=`apt-cache search php-fpm|grep -e '[[:digit:]]\.[[:digit:]]' -o`
if [ "$PHP_VER" = "" ] ; then PHP_VER="8.1"; fi
for pkg in "make clang flex fp-compiler g++ mono-devel openjdk-11-jdk net-tools nginx php$PHP_VER-common php$PHP_VER-curl php$PHP_VER-fpm php$PHP_VER-gd php$PHP_VER-intl php$PHP_VER-mbstring php$PHP_VER-mysql php$PHP_VER-soap php$PHP_VER-xml php$PHP_VER-xmlrpc php$PHP_VER-zip bzip2 php-apcu php-yaml tzdata"
do
    while ! apt-get install -y $pkg 
    do
        echo "Network fail, retry... you might want to change another apt source for install"
    done
done
# for pkg in "memcached php-memcache php-memcached"
# do
#     while ! apt-get install -y $pkg 
#     do
#         echo "Network fail, retry... you might want to change another apt source for install"
#     done
# done
for pkg in "libtiff5-dev libjpeg8-dev libopenjp2-7-dev zlib1g-dev libfreetype6-dev liblcms2-dev libwebp-dev tcl8.6-dev tk8.6-dev python3-tk libharfbuzz-dev libfribidi-dev libxcb1-dev python3-pip"
do
    while ! apt-get install -y $pkg 
    do
        echo "Network fail, retry... you might want to change another apt source for install"
    done
done
reset
echo ""
echo " ██      ██ ████████ ████     ██ ██     ██   ███████        ██"
echo "░██     ░██░░░░░░██ ░██░██   ░██░██    ░██  ██░░░░░██      ░██"
echo "░██     ░██     ██  ░██░░██  ░██░██    ░██ ██     ░░██     ░██"
echo "░██████████    ██   ░██ ░░██ ░██░██    ░██░██      ░██     ░██"
echo "░██░░░░░░██   ██    ░██  ░░██░██░██    ░██░██      ░██     ░██"
echo "░██     ░██  ██     ░██   ░░████░██    ░██░░██     ██  ██  ░██"
echo "░██     ░██ ████████░██    ░░███░░███████  ░░███████  ░░█████ "
echo "░░      ░░ ░░░░░░░░ ░░      ░░░  ░░░░░░░    ░░░░░░░    ░░░░░  "
echo ""
# /usr/sbin/useradd -m -u 1536 judge
/usr/sbin/useradd -m -u 1536 -s /sbin/nologin judge
chgrp www-data /home/judge
cp -R HZNUOJ /home/judge/

cd /home/judge/ || exit

USER=`cat /etc/mysql/debian.cnf |grep user|head -1|awk  '{print $3}'`
PASSWORD=`cat /etc/mysql/debian.cnf |grep password|head -1|awk  '{print $3}'`
CPU=`grep "cpu cores" /proc/cpuinfo |head -1|awk '{print $4}'`

mkdir etc data log backup
mkdir -p /home/judge/HZNUOJ/web/OJ/upload

cp HZNUOJ/judger/install/java0.policy /home/judge/etc
cp HZNUOJ/judger/install/judge.conf /home/judge/etc
chmod +x HZNUOJ/judger/install/*

# create enough runX dirs for each CPU core
for N in `seq 0 $(($CPU-1))`
do
    mkdir run$N
    chown judge run$N
done

sed -i "s/OJ_USER_NAME=.*/OJ_USER_NAME=$USER/g" etc/judge.conf
sed -i "s/OJ_PASSWORD=.*/OJ_PASSWORD=$PASSWORD/g" etc/judge.conf
sed -i "s/OJ_COMPILE_CHROOT=1/OJ_COMPILE_CHROOT=0/g" etc/judge.conf
sed -i "s/OJ_RUNNING=1/OJ_RUNNING=$CPU/g" etc/judge.conf

chmod 700 backup
chmod 700 etc/judge.conf
chown -R root:root etc

sed -i "s/DB_USER[[:space:]]*=[[:space:]]*\".*\"/DB_USER=\"$USER\"/g" HZNUOJ/web/OJ/include/static.php
sed -i "s/DB_PASS[[:space:]]*=[[:space:]]*\".*\"/DB_PASS=\"$PASSWORD\"/g" HZNUOJ/web/OJ/include/static.php

mkdir -p /home/judge/data/1000
pushd /home/judge/data/1000
    echo "1 2" > sample0.in
    echo "3" > sample0.out
    echo "6 10" > test0.in
    echo "16" > test0.out
    echo "6 9" > test1.in
    echo "15" > test1.out
    echo "0 0" > test2.in
    echo "0" > test2.out
popd
chmod 700 HZNUOJ/web/OJ/include/static.php
chown -R www-data:www-data HZNUOJ/web/
chown -R www-data:www-data HZNUOJ/web/OJ/upload
chmod 770 -R HZNUOJ/web/OJ/upload
chown -R www-data:judge data
chmod 750 -R data

if grep "client_max_body_size" /etc/nginx/nginx.conf ; then 
    echo "client_max_body_size already added" ;
else
    sed -i 's/# multi_accept on;/ multi_accept on;/' /etc/nginx/nginx.conf
    sed -i "s:include /etc/nginx/mime.types;:client_max_body_size    500m;\n\tinclude /etc/nginx/mime.types;:g" /etc/nginx/nginx.conf
fi

mysql -h localhost -u$USER -p$PASSWORD < HZNUOJ/judger/install/db.sql

if grep "added by hustoj" /etc/nginx/sites-enabled/default ; then
    echo "default site modified!"
else
    echo "modify the default site"

    sed -i "s#listen 80 default_server;#listen 80 default_server backlog=4096;#g" /etc/nginx/sites-enabled/default
    sed -i "s#root /var/www/html;#root /home/judge/HZNUOJ/web/OJ;#g" /etc/nginx/sites-enabled/default
    sed -i "s:index index.html:index index.php:g" /etc/nginx/sites-enabled/default
    sed -i "s:#location ~ \\\.php\\$:location ~ \\\.php\\$:g" /etc/nginx/sites-enabled/default
    sed -i "s:#\tinclude snippets:\tinclude snippets:g" /etc/nginx/sites-enabled/default
    sed -i "s|#\tfastcgi_pass unix|\tfastcgi_pass unix|g" /etc/nginx/sites-enabled/default
    sed -i "s:}#added by hustoj::g" /etc/nginx/sites-enabled/default
    sed -i "s:php7.4:php$PHP_VER:g" /etc/nginx/sites-enabled/default
    sed -i "s|# deny access to .htaccess files|}#added by hustoj\n\n\n\t# deny access to .htaccess files|g" /etc/nginx/sites-enabled/default
    sed -i "s|fastcgi_pass 127.0.0.1:9000;|fastcgi_pass 127.0.0.1:9001;\n\t\tfastcgi_buffer_size 256k;\n\t\tfastcgi_buffers 32 64k;|g" /etc/nginx/sites-enabled/default
fi
/etc/init.d/nginx restart
sed -i "s/post_max_size = 8M/post_max_size = 500M/g" /etc/php/$PHP_VER/fpm/php.ini
# sed -i "s#;date.timezone =#date.timezone = Asia/Shanghai#g" /etc/php/$PHP_VER/fpm/php.ini
sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 500M/g" /etc/php/$PHP_VER/fpm/php.ini
if grep "opcache.jit_buffer_size" /etc/php/$PHP_VER/fpm/php.ini ; then
    echo "opcache for jit is already enabled ... "
else
    sed -i "s|opcache.lockfile_path=/tmp|opcache.lockfile_path=/tmp\nopcache.jit_buffer_size=16M|g" /etc/php/$PHP_VER/fpm/php.ini
fi
WWW_CONF=$(find /etc/php -name www.conf)
sed -i 's/;request_terminate_timeout = 0/request_terminate_timeout = 128/g' "$WWW_CONF"
sed -i 's/pm.max_children = 5/pm.max_children = 600/g' "$WWW_CONF"
sed -i 's/;listen.backlog = 511/listen.backlog = 4096/g' "$WWW_CONF"

COMPENSATION=$(grep 'mips' /proc/cpuinfo|head -1|awk -F: '{printf("%.2f",$2/7000)}')
sed -i "s/OJ_CPU_COMPENSATION=1.0/OJ_CPU_COMPENSATION=$COMPENSATION/g" etc/judge.conf

PHP_FPM=$(find /etc/init.d/ -name "php*-fpm")
$PHP_FPM restart
PHP_FPM=$(service --status-all|grep php|awk '{print $4}')
if [ "$PHP_FPM" != ""  ]; then service "$PHP_FPM" restart ;else echo "NO PHP FPM";fi;

cd HZNUOJ/judger/core || exit
chmod +x ./make.sh
./make.sh
if grep "/usr/bin/judged" /etc/rc.local ; then
    echo "auto start judged added!"
else
    sed -i "s/exit 0//g" /etc/rc.local
    echo "/usr/bin/judged" >> /etc/rc.local
    echo "exit 0" >> /etc/rc.local
    echo "add auto start judged."
fi
if grep "bak.sh" /var/spool/cron/crontabs/root ; then
    echo "auto backup added!"
else
    crontab -l > conf 
    echo "1 0 * * * /home/judge/HZNUOJ/judger/install/bak.sh" >> conf
    echo "0 * * * * /home/judge/HZNUOJ/judger/install/oomsaver.sh" >> conf 
    crontab conf 
    rm -f conf
    /etc/init.d/cron reload
fi

ln -s /usr/bin/mcs /usr/bin/gmcs

/usr/bin/judged
cp /home/judge/HZNUOJ/judger/install/hustoj /etc/init.d/hustoj
update-rc.d hustoj defaults
systemctl enable hustoj
systemctl enable nginx
systemctl enable mysql
systemctl enable php$PHP_VER-fpm
# systemctl enable judged

# if ps -C memcached; then 
#     sed -i 's/static  $OJ_MEMCACHE=false;/static  $OJ_MEMCACHE=true;/g' /home/judge/HZNUOJ/web/OJ/include/static.php
#     sed -i 's/-m 64/-m 8/g' /etc/memcached.conf
#     /etc/init.d/memcached restart
# fi

mkdir /var/log/hustoj/
chown www-data -R /var/log/hustoj/

reset
echo ""
echo " ██      ██ ████████ ████     ██ ██     ██   ███████        ██"
echo "░██     ░██░░░░░░██ ░██░██   ░██░██    ░██  ██░░░░░██      ░██"
echo "░██     ░██     ██  ░██░░██  ░██░██    ░██ ██     ░░██     ░██"
echo "░██████████    ██   ░██ ░░██ ░██░██    ░██░██      ░██     ░██"
echo "░██░░░░░░██   ██    ░██  ░░██░██░██    ░██░██      ░██     ░██"
echo "░██     ░██  ██     ░██   ░░████░██    ░██░░██     ██  ██  ░██"
echo "░██     ░██ ████████░██    ░░███░░███████  ░░███████  ░░█████ "
echo "░░      ░░ ░░░░░░░░ ░░      ░░░  ░░░░░░░    ░░░░░░░    ░░░░░  "
echo ""
echo "OJ Configuration:"
echo ""
printf "1-Please input OJ's name, press Enter for default name(argument:\$OJ_NAME): "
read ojname
if test "$ojname" != ""
then
    sed -i "s/OJ_NAME=\"HZNUOJ\"/OJ_NAME=\"$ojname\"/g" /home/judge/HZNUOJ/web/OJ/include/static.php
fi
echo ""
echo "2-Please select the UI language.(argument:\$OJ_LANG)"
echo "  1) Chinese"
echo "  2) English"
temp=0
while test $temp != 1 -a $temp != 2
do
    printf "#? "
    read temp
done
if test $temp = 1
then
    sed -i "s/OJ_LANG=\"en\"/OJ_LANG=\"cn\"/g" /home/judge/HZNUOJ/web/OJ/include/static.php
else
    sed -i "s/OJ_LANG=\"cn\"/OJ_LANG=\"en\"/g" /home/judge/HZNUOJ/web/OJ/include/static.php
fi
echo ""
echo "3-Please select running mode.(argument:OJ_OI_MODE)"
echo "  1)  OI Mode (Middle school)"
echo "  2) ACM Mode (University)"
temp=0
while test $temp != 1 -a $temp != 2
do
    printf "#? "
    read temp
done
if test $temp = 1
then
    sed -i "s/OJ_OI_MODE=0/OJ_OI_MODE=1/g" /home/judge/etc/judge.conf
else
    sed -i "s/OJ_OI_MODE=1/OJ_OI_MODE=0/g" /home/judge/etc/judge.conf
fi
echo ""
echo "4-Please select trun on/off the code share mode.(argument:\$OJ_AUTO_SHARE)"
echo "  1) Trun on  (All of users are able to view all submissions after solving this problem.)"
echo "  2) Trun off (Only administrators are able to view all submissions.)"
temp=0
while test $temp != 1 -a $temp != 2
do
    printf "#? "
    read temp
done
if test $temp = 2
then
    sed -i "s/OJ_AUTO_SHARE=true/OJ_AUTO_SHARE=false/g" /home/judge/HZNUOJ/web/OJ/include/static.php
else
    sed -i "s/OJ_AUTO_SHARE=false/OJ_AUTO_SHARE=true/g" /home/judge/HZNUOJ/web/OJ/include/static.php
fi
echo ""
echo "5-Please select trun on/off show the WA/CE information in reinfo/ceinfo page.(argument:\$OJ_SHOW_DIFF)"
echo "1) Trun on  (All of users are able to view the WA/CE information of their own code.)"
echo "2) Trun off (Only administrators are able to view the WA/CE information.)"
temp=0
while test $temp != 1 -a $temp != 2
do
    printf "#? "
    read temp
done
if test $temp = 2
then
    sed -i "s/OJ_SHOW_DIFF=true/OJ_SHOW_DIFF=false/g" /home/judge/HZNUOJ/web/OJ/include/static.php
else
    sed -i "s/OJ_SHOW_DIFF=false/OJ_SHOW_DIFF=true/g" /home/judge/HZNUOJ/web/OJ/include/static.php
fi
echo ""
echo "6-Please select trun on/off source code similarity detection.(argument:\$OJ_SIM, OJ_SIM_ENABLE)"
echo "1) Trun on"
echo "2) Trun off"
temp=0
while test $temp != 1 -a $temp != 2
do
    printf "#? "
    read temp
done
if test $temp = 2
then
    sed -i "s/OJ_SIM=true/OJ_SIM=false/g" /home/judge/HZNUOJ/web/OJ/include/static.php
    sed -i "s/OJ_SIM_ENABLE=1/OJ_SIM_ENABLE=0/g" /home/judge/etc/judge.conf
else
    sed -i "s/OJ_SIM=false/OJ_SIM=true/g" /home/judge/HZNUOJ/web/OJ/include/static.php
    sed -i "s/OJ_SIM_ENABLE=0/OJ_SIM_ENABLE=1/g" /home/judge/etc/judge.conf
fi
echo ""
echo "7-Please select trun on/off show the contest's solution in status page.(argument:\$OJ_show_contestSolutionInStatus)"
echo "1) Trun on  (contest's solution will be show in status page and contest-status page.)"
echo "2) Trun off (contest's solution will be show in contest-status page only.)"
temp=0
while test $temp != 1 -a $temp != 2
do
    printf "#? "
    read temp
done
if test $temp = 1
then
    sed -i "s/OJ_show_contestSolutionInStatus=false/OJ_show_contestSolutionInStatus=true/g" /home/judge/HZNUOJ/web/OJ/include/static.php
else
    sed -i "s/OJ_show_contestSolutionInStatus=true/OJ_show_contestSolutionInStatus=false/g" /home/judge/HZNUOJ/web/OJ/include/static.php
fi
echo ""
echo "Install HZNUOJ successfully!"
echo "Remember your database account for HZNUOJ:"
echo "username:$USER"
echo "password:$PASSWORD"
