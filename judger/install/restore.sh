#/bin/bash
TARBZNAME=`find -name "HZNUOJ_*.tar.bz2"`
if [ $# != 1 ] ; then
  echo "USAGE: sudo $0 $TARBZNAME"
  echo " e.g.: sudo $0 HZNUOJ_xxxxxxx.tar.bz2"
  echo " tar.bz2 should be created by bak.sh, default location : /var/backups/ "
  exit 1;
fi

DATE=`date +%Y%m%d%H%M%S`
BAKDATE=`echo $1 |awk -F\. '{print $1}'|awk -F_ '{print $2}'`
config="/home/judge/etc/judge.conf"
SERVER=`cat $config|grep 'OJ_HOST_NAME' |awk -F= '{print $2}'`
USER=`cat $config|grep 'OJ_USER_NAME' |awk -F= '{print $2}'`
PASSWORD=`cat $config|grep 'OJ_PASSWORD' |awk -F= '{print $2}'`
DATABASE=`cat $config|grep 'OJ_DB_NAME' |awk -F= '{print $2}'`
web_user=`grep www /etc/passwd|awk -F: '{print $1}'`
echo "Restore starting..."
chown $web_user -R /home/judge/HZNUOJ/web/OJ/upload
chmod 770 -R /home/judge/HZNUOJ/web/OJ/upload
mkdir HZNUOJ-restore
cd HZNUOJ-restore
MAIN="../$1"
echo "Backup file"
/home/judge/HZNUOJ/judger/install/bak.sh
echo "Restore data"
tar xjf $MAIN
mv /home/judge/data /home/judge/data.del.$DATE
mv home/judge/data /home/judge/
chown  $web_user:judge  -R /home/judge/data
chmod 750 -R /home/judge/data
echo "Restore upload"
mv /home/judge/HZNUOJ/web/OJ/upload /home/judge/HZNUOJ/web/upload.del.$DATE
mv home/judge/HZNUOJ/web/OJ/upload /home/judge/HZNUOJ/web/OJ/
chown  $web_user -R /home/judge/HZNUOJ/web/
echo "Restore database"
bzip2 -d var/backups/db_${BAKDATE}.sql.bz2
sed -i 's/COLLATE=utf8mb4_0900_ai_ci//g' var/backups/db_${BAKDATE}.sql
sed -i 's/COLLATE utf8mb4_0900_ai_ci//g' var/backups/db_${BAKDATE}.sql
sed -i 's/utf8mb4_0900_ai_ci/utf8mb4_general_ci/g' var/backups/db_${BAKDATE}.sql
if ! mysql -h $SERVER -u$USER -p$PASSWORD $DATABASE < var/backups/db_${BAKDATE}.sql ; then
   mysql $DATABASE < var/backups/db_${BAKDATE}.sql
fi
# if ! mysql -h $SERVER -u$USER -p$PASSWORD $DATABASE < /home/judge/HZNUOJ/judger/install/update.sql ; then
#    mysql $DATABASE < /home/judge/HZNUOJ/judger/install/update.sql
# fi
cd ..
rm -rf HZNUOJ-restore
echo "Well done!"