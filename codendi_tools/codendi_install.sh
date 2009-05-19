#!/bin/bash
#
# Copyright (c) Xerox Corporation, Codendi 2001-2009.
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
#      Originally written by Laurent Julliard 2004, Codendi Team, Xerox
#
#  This file is part of the Codendi software and must be place at the same
#  level as the Codendi, RPMS_Codendi and nonRPMS_Codendi directory when
#  delivered on a CD or by other means
#

# In order to keep a log of the installation, you may run the script with:
# ./codendi_install.sh 2>&1 | tee /tmp/codendi_install.log

progname=$0
#scriptdir=/mnt/cdrom
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd ${scriptdir};TOP_DIR=`pwd`;cd - > /dev/null # redirect to /dev/null to remove display of folder (RHEL4 only)
RPMS_DIR=${TOP_DIR}/RPMS_Codendi
nonRPMS_DIR=${TOP_DIR}/nonRPMS_Codendi
Codendi_DIR=${TOP_DIR}/Codendi
TODO_FILE=/root/todo_codendi.txt
export INSTALL_DIR="/usr/share/codendi"

# path to command line tools
GROUPADD='/usr/sbin/groupadd'
GROUPDEL='/usr/sbin/groupdel'
USERADD='/usr/sbin/useradd'
USERDEL='/usr/sbin/userdel'
USERMOD='/usr/sbin/usermod'
MV='/bin/mv'
CP='/bin/cp'
LN='/bin/ln'
LS='/bin/ls'
RM='/bin/rm'
TAR='/bin/tar'
MKDIR='/bin/mkdir'
RPM='/bin/rpm'
CHOWN='/bin/chown'
CHGRP='/bin/chgrp'
CHMOD='/bin/chmod'
FIND='/usr/bin/find'
MYSQL='/usr/bin/mysql'
TOUCH='/bin/touch'
CAT='/bin/cat'
MAKE='/usr/bin/make'
TAIL='/usr/bin/tail'
GREP='/bin/grep'
CHKCONFIG='/sbin/chkconfig'
SERVICE='/sbin/service'
PERL='/usr/bin/perl'
DIFF='/usr/bin/diff'
PHP='/usr/bin/php'

CHCON='/usr/bin/chcon'
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
$GREP -i -q '^SELINUX=disabled' /etc/selinux/config
if [ $? -eq 0 ] || [ ! -e $CHCON ] || [ ! -e "/etc/selinux/config" ] ; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi


CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL DIFF"

# Functions
create_group() {
    # $1: groupname, $2: groupid
    $GROUPDEL "$1" 2>/dev/null
    $GROUPADD -g "$2" "$1"
}

build_dir() {
    # $1: dir path, $2: user, $3: group, $4: permission
    $MKDIR -p "$1" 2>/dev/null; $CHOWN "$2.$3" "$1";$CHMOD "$4" "$1";
}

make_backup() {
    # $1: file name, $2: extension for old file (optional)
    file="$1"
    ext="$2"
    if [ -z $ext ]; then
	ext="nocodendi"
    fi
    backup_file="$1.$ext"
    [ -e "$file" -a ! -e "$backup_file" ] && $CP "$file" "$backup_file"
}

todo() {
    # $1: message to log in the todo file
    echo -e "- $1" >> $TODO_FILE
}

die() {
  # $1: message to prompt before exiting
  echo -e "**ERROR** $1"; exit 1
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
}

##############################################
# Codendi installation
##############################################

##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Check we are running on RHEL 5.3 
# 5.3 is needed for openjdk. This will need to be updated when 5.4 is available!
#
RH_RELEASE="5"
yn="y"
$RPM -q redhat-release-${RH_RELEASE}* | grep 5-3 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
  $RPM -q centos-release-${RH_RELEASE}* | grep 5-3 2>/dev/null 1>&2
  if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE} or CentOS  ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [y|n]: " yn
  else
    echo "Running on CentOS ${RH_RELEASE}... good!"
  fi
else
    echo "Running on RedHat Enterprise Linux ${RH_RELEASE}... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

rm -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODENDI INSTALLATION (see $TODO_FILE)"


##############################################
# Check Required Stock RedHat RPMs are installed
#
# gd-devel freetype-devel libpng-devel libjpeg-devel -> cvsgraph
# xorg-x11-deprecated-libs -> docbook/java
# libart_lgpl perl-Digest-SHA1 perl-Digest-HMAC perl-Socket6 -> munin
# zip, unzip -> CLI client
# dump -> backup_job
# dejavu-lgc-fonts -> jpgraph
#rpm -Uvh cpp-3.4.6-3.i386.rpm gcc-3.4.6-3.i386.rpm  libgcc-3.4.6-3.i386.rpm gcc-c++-3.4.6-3.i386.rpm gcc-g77-3.4.6-3.i386.rpm gcc-java-3.4.6-3.i386.rpm libstdc++-* libf2c-3.4.6-3.i386.rpm libgcj-3.4.6-3.i386.rpm libgcj-devel-3.4.6-3.i386.rpm
# php-xml -> jabbex
# Missing on CentOS 5: xorg-x11-deprecated-libs httpd-suexec
# compat-libstdc++-33 -> CVSnt
# apr apr-util -> svn
# xinetd -> cvs
#    policycoreutils coreutils selinux-policy selinux-policy-targeted libselinux -> SELinux 
rpms_ok=1
for rpm in openssh-server openssh openssh-clients \
   httpd  apr apr-util mod_ssl vsftpd \
   openssl openldap perl perl-DBI perl-DBD-MySQL gd \
   sendmail telnet bind bind-chroot caching-nameserver ntp python perl-suidperl \
   python-devel rcs sendmail-cf perl-URI perl-HTML-Tagset perl-Digest-SHA1 perl-Digest-HMAC perl-Socket6 \
   perl-HTML-Parser perl-libwww-perl php php-ldap php-mysql mysql-server \
   mysql MySQL-python php-mbstring php-gd php-soap php-xml php-pear \
   perl-DateManip sysstat curl aspell \
   gd-devel freetype-devel libpng-devel libjpeg-devel \
   libart_lgpl  \
   dump \
   dejavu-lgc-fonts \
   compat-libstdc++-33 \
   policycoreutils coreutils selinux-policy selinux-policy-targeted libselinux \
   java-1.6.0-openjdk jpackage-utils giflib\
   zip unzip enscript xinetd mod_auth_mysql nss nscd
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ $? -eq 1 ]; then
	rpms_ok=0
	missing_rpms="$missing_rpms $rpm"
    fi
done
if [ $rpms_ok -eq 0 ]; then
    msg="The following Redhat Linux RPMs must be installed first:\n"
    msg="${msg}$missing_rpms\n"
    msg="${msg}Get them from your Redhat CDROM or FTP site, install them and re-run the installation script"
    die "$msg"
fi
echo "All requested RedHat RPMS installed... good!"


##############################################
# Create Groups and Users
#

make_backup /etc/passwd
make_backup /etc/shadow
make_backup /etc/group

# Delete users that could be part of the groups (otherwise groupdel fails!)
for u in mailman dummy codendiadm ftp ftpadmin
do
    $USERDEL $u 2>/dev/null 1>&2
done

# Create Groups
create_group codendiadm 104
create_group dummy 103
create_group mailman 106
create_group ftpadmin 96
create_group ftp 50

# Ask for domain name and other installation parameters
read -p "Codendi Domain name: " sys_default_domain
read -p "Your Company short name (e.g. Xerox): " sys_org_name
read -p "Your Company long name (e.g. Xerox Corporation): " sys_long_org_name
read -p "Codendi Server fully qualified machine name: " sys_fullname
read -p "Codendi Server IP address: " sys_ip_address
read -p "LDAP server name: " sys_ldap_server
read -p "Activate user shell accounts? [y|n]:" active_shell
read -p "Generate a self-signed SSL certificate to enable HTTPS support? [y|n]:" create_ssl_certificate
read -p "Disable sub-domain management (no DNS delegation)? [y|n]:" disable_subdomains

# Ask for user passwords
rt_passwd="a"; rt_passwd2="b";
while [ "$rt_passwd" != "$rt_passwd2" ]; do
    read -s -p "Password for MySQL root: " rt_passwd
    echo
    read -s -p "Retype MySQL root password: " rt_passwd2
    echo
done

codendiadm_passwd="a"; codendiadm_passwd2="b";
while [ "$codendiadm_passwd" != "$codendiadm_passwd2" ]; do
    read -s -p "Password for user codendiadm: " codendiadm_passwd
    echo
    read -s -p "Retype codendiadm password: " codendiadm_passwd2
    echo
done

mm_passwd="a"; mm_passwd2="b";
while [ "$mm_passwd" != "$mm_passwd2" ]; do
    read -s -p "Password for user mailman: " mm_passwd
    echo
    read -s -p "Retype mailman password: " mm_passwd2
    echo
done

slm_passwd="a"; slm_passwd2="b";
while [ "$slm_passwd" != "$slm_passwd2" ]; do
    read -s -p "Password for Salome DB user: " slm_passwd
    echo
    read -s -p "Retype password for Salome DB user: " slm_passwd2
    echo
done

openfire_passwd="a"; openfire_passwd2="b";
while [ "$openfire_passwd" != "$openfire_passwd2" ]; do
    read -s -p "Password for Openfire DB user: " openfire_passwd
    echo
    read -s -p "Retype password for Openfire DB user: " openfire_passwd2
    echo
done

dbauth_passwd="a"; dbauth_passwd2="b";
while [ "$dbauth_passwd" != "$dbauth_passwd2" ]; do
    read -s -p "Password for DB Authentication user: " dbauth_passwd
    echo
    read -s -p "Retype password for DB Authentication user: " dbauth_passwd2
    echo
done

#py_cmd="import crypt; print crypt.crypt(\"$rt_passwd\",\"\$1\$e4h67niB\$\")"
#rt_encpasswd=`python -c "$py_cmd"`
py_cmd="import crypt; print crypt.crypt(\"$codendiadm_passwd\",\"\$1\$h67e4niB\$\")"
codendi_encpasswd=`python -c "$py_cmd"`
py_cmd="import crypt; print crypt.crypt(\"$mm_passwd\",\"\$1\$eniB4h67\$\")"
mm_encpasswd=`python -c "$py_cmd"`

# Create Users

# No longer modify root password. It is not safe to do this in a script.
#$USERMOD -p "$rt_encpasswd" root

$USERDEL codendiadm 2>/dev/null 1>&2
$USERADD -c 'Owner of Codendi directories' -M -d '/home/codendiadm' -p "$codendi_encpasswd" -u 104 -g 104 -s '/bin/bash' -G ftpadmin,mailman codendiadm
# mailman group needed to write in /var/log/mailman/ directory

$USERDEL mailman 2>/dev/null 1>&2
$USERADD -c 'Owner of Mailman directories' -M -d '/usr/lib/mailman' -p "$mm_encpasswd" -u 106 -g 106 -s '/sbin/nologin' mailman

$USERDEL ftpadmin 2>/dev/null 1>&2
$USERADD -c 'FTP Administrator' -M -d '/var/lib/codendi/ftp' -u 96 -g 96 ftpadmin

$USERDEL ftp 2>/dev/null 1>&2
$USERADD -c 'FTP User' -M -d '/var/lib/codendi/ftp' -u 14 -g 50 ftp

$USERDEL dummy 2>/dev/null 1>&2
$USERADD -c 'Dummy Codendi User' -M -d '/var/lib/codendi/dumps' -u 103 -g 103 dummy

# Build file structure

build_dir $INSTALL_DIR codendiadm codendiadm 775
#build_dir $INSTALL_DIR/downloads codendiadm codendiadm 775
build_dir /home/users codendiadm codendiadm 771
build_dir /home/groups codendiadm codendiadm 771

# home directories
build_dir /home/codendiadm codendiadm codendiadm 700
# data dirs
build_dir /var/lib/codendi codendiadm codendiadm 755
build_dir /var/lib/codendi/dumps dummy dummy 755
build_dir /var/lib/codendi/ftp root ftp 755
#build_dir /var/lib/codendi/ftp/bin ftpadmin ftpadmin 111
#build_dir /var/lib/codendi/ftp/etc ftpadmin ftpadmin 111
#build_dir /var/lib/codendi/ftp/lib ftpadmin ftpadmin 755
build_dir /var/lib/codendi/ftp/codendi root root 711
build_dir /var/lib/codendi/ftp/pub ftpadmin ftpadmin 755
build_dir /var/lib/codendi/ftp/incoming ftpadmin ftpadmin 3777
build_dir /var/lib/codendi/wiki codendiadm codendiadm 700
build_dir /var/lib/codendi/backup codendiadm codendiadm 711
build_dir /var/lib/codendi/backup/mysql mysql mysql 770 
build_dir /var/lib/codendi/backup/mysql/old root root 700
build_dir /var/lib/codendi/backup/subversion root root 700
build_dir /var/lib/codendi/docman codendiadm codendiadm 700
# log dirs
build_dir /var/log/codendi codendiadm codendiadm 755
build_dir /var/log/codendi/cvslogs codendiadm codendiadm 775
build_dir /var/tmp/codendi_cache codendiadm codendiadm 755
# bin dirs
build_dir /usr/lib/codendi codendiadm codendiadm 755
build_dir /usr/lib/codendi/bin codendiadm codendiadm 755
# config dirs
build_dir /etc/skel_codendi root root 755
build_dir /etc/codendi codendiadm codendiadm 755
build_dir /etc/codendi/conf codendiadm codendiadm 700
build_dir /etc/codendi/documentation codendiadm codendiadm 755
build_dir /etc/codendi/documentation/user_guide codendiadm codendiadm 755
build_dir /etc/codendi/documentation/user_guide/xml codendiadm codendiadm 755
build_dir /etc/codendi/documentation/cli codendiadm codendiadm 755
build_dir /etc/codendi/documentation/cli/xml codendiadm codendiadm 755
build_dir /etc/codendi/site-content codendiadm codendiadm 755
build_dir /etc/codendi/site-content/en_US codendiadm codendiadm 755
build_dir /etc/codendi/site-content/en_US/others codendiadm codendiadm 755
build_dir /etc/codendi/site-content/fr_FR codendiadm codendiadm 755
build_dir /etc/codendi/site-content/fr_FR/others codendiadm codendiadm 755
build_dir /etc/codendi/themes codendiadm codendiadm 755
build_dir /etc/codendi/plugins codendiadm codendiadm 755
build_dir /etc/codendi/plugins/docman codendiadm codendiadm 755
build_dir /etc/codendi/plugins/pluginsadministration codendiadm codendiadm 755
build_dir /etc/codendi/plugins/serverupdate codendiadm codendiadm 755
# SCM dirs
build_dir /var/run/log_accum root root 777
build_dir /var/lib/codendi/cvsroot codendiadm codendiadm 751
build_dir /var/lib/codendi/svnroot codendiadm codendiadm 751
build_dir /var/lock/cvs root root 751
$LN -sf /var/lib/codendi/cvsroot /cvsroot
$LN -sf /var/lib/codendi/svnroot /svnroot


$TOUCH /var/lib/codendi/ftp/incoming/.delete_files
$CHOWN codendiadm.ftpadmin /var/lib/codendi/ftp/incoming/.delete_files
$CHMOD 750 /var/lib/codendi/ftp/incoming/.delete_files
$TOUCH /var/lib/codendi/ftp/incoming/.delete_files.work
$CHOWN codendiadm.ftpadmin /var/lib/codendi/ftp/incoming/.delete_files.work
$CHMOD 750 /var/lib/codendi/ftp/incoming/.delete_files.work
build_dir /var/lib/codendi/ftp/codendi/DELETED codendiadm codendiadm 750

$TOUCH /etc/httpd/conf.d/codendi_svnroot.conf

# SELinux specific
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/codendi
    $CHCON -R -h $SELINUX_CONTEXT /etc/codendi
    $CHCON -R -h $SELINUX_CONTEXT /var/lib/codendi
    $CHCON -R -h $SELINUX_CONTEXT /home/groups
    $CHCON -R -h $SELINUX_CONTEXT /home/codendiadm
    $CHCON -h $SELINUX_CONTEXT /svnroot
    $CHCON -h $SELINUX_CONTEXT /cvsroot
fi


##############################################
# Move away useless Apache configuration files
# before installing our own config files.
#
echo "Renaming existing Apache configuration files..."
cd /etc/httpd/conf.d/
for f in *.conf
do
    yn="0"
    current_name="$f"
    orig_name="$f.rhel"
    [ -f "$orig_name" ] && read -p "$orig_name already exist. Overwrite? [y|n]:" yn

    if [ "$yn" != "n" ]; then
	$MV -f $current_name $orig_name
    fi

    if [ "$yn" = "n" ]; then
	$RM -f $current_name
    fi
    # In order to prevent RedHat from reinstalling those files during an RPM update, re-create an empty file for each file deleted
    $TOUCH $current_name
done
cd - > /dev/null

######
# Now install Codendi specific RPMS (and remove RedHat RPMs)
#


# -> cvs
echo "Removing existing CVS .."
$RPM -e --allmatches cvs 2>/dev/null
echo "Installing CVS RPMs for Codendi...."
cd ${RPMS_DIR}/cvs
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/cvs-1.*.i386.rpm

# -> subversion
# Neon is used by other RPMS (cadaver...) that will conflict when upgrading the RPM
$RPM --quiet -q cadaver
if [ $? -eq 0 ]; then
  echo "Removing Cadaver RPM (conflicts with newer Neon libs)"
  $RPM -e --allmatches cadaver
fi
echo "Removing existing Subversion and Neon RPMs if any...."
$RPM -e --allmatches subversion-tools  2>/dev/null
$RPM -e --allmatches subversion-devel 2>/dev/null
$RPM -e --allmatches mod_dav_svn 2>/dev/null
$RPM -e --allmatches subversion-perl 2>/dev/null
$RPM -e --allmatches subversion-python 2>/dev/null
$RPM -e --allmatches subversion 2>/dev/null
$RPM -e --allmatches neon-devel 2>/dev/null
$RPM -e --allmatches neon 2>/dev/null
echo "Installing Subversion, Neon and recent SQLite RPMs for Codendi...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
cd ${newest_rpm}
# Update SQLite first: version above 3.4 is required for SVN 1.6, and RHEL5 only provides version 3.3.
$RPM -Uvh sqlite-3*.i386.rpm
$RPM -ivh neon-0.*.i386.rpm neon-devel*.i386.rpm subversion-1.*.i386.rpm mod_dav_svn*.i386.rpm subversion-perl*.i386.rpm subversion-python*.i386.rpm 
# Dependency error with Perl ??
$RPM --nodeps -Uvh subversion-tools*.i386.rpm

# -> libnss-mysql (system authentication based on MySQL)
$RPM -e --allmatches libnss-mysql 2>/dev/null
echo "Installing libnss-mysql RPM for Codendi...."
cd ${RPMS_DIR}/libnss-mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --nosignature ${newest_rpm}/libnss-mysql-1*i?86.rpm

# -> cvsgraph 
$RPM -e --allmatches cvsgraph 2>/dev/null
echo "Installing cvsgraph RPM for Codendi...."
cd ${RPMS_DIR}/cvsgraph
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/cvsgraph-1*i?86.rpm

# -> highlight
$RPM -e --allmatches highlight 2>/dev/null
echo "Installing highlight RPM for Codendi...."
cd ${RPMS_DIR}/highlight
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/highlight-2*i?86.rpm

# -> JPGraph
$RPM -e jpgraph jpgraphs-docs 2>/dev/null
echo "Installing JPGraph RPM for Codendi...."
cd ${RPMS_DIR}/jpgraph
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/jpgraph-2*noarch.rpm
$RPM -Uvh ${newest_rpm}/jpgraph-docs-2*noarch.rpm

# -> ViewVC
$RPM -e --nodeps viewcvs 2>/dev/null
$RPM -e --nodeps viewvc 2>/dev/null
echo "Installing viewvc RPM for Codendi...."
cd ${RPMS_DIR}/viewvc
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/viewvc-*.noarch.rpm

# -> phpMyAdmin
$RPM -e phpMyAdmin phpmyadmin 2>/dev/null
echo "Installing phpMyAdmin RPM for Codendi...."
cd ${RPMS_DIR}/phpMyAdmin
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/phpmyadmin-*.noarch.rpm

# -> APC
$RPM -e php-pecl-apc 2>/dev/null
echo "Installing APC (PHP cache) RPM for Codendi...."
cd ${RPMS_DIR}/php-pecl-apc
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/php-pecl-apc-*.i?86.rpm

# -> mailman
echo "Removing installed mailman if any .."
$RPM -e --allmatches mailman 2>/dev/null
echo "Installing mailman RPM for Codendi...."
cd ${RPMS_DIR}/mailman
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/mailman-2*i?86.rpm

# Munin
echo "Removing installed Munin if any .."
$RPM -e --allmatches `rpm -qa 'munin*' 'perl-HTML-Template*' 'perl-Net-Server' 'perl-rrdtool*' 'rrdtool*' 'perl-Crypt-DES' 'perl-Net-SNMP' 'perl-Config-General'` 2>/dev/null
echo "Installing Munin RPMs for Codendi...."
cd ${RPMS_DIR}/munin
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM --nosignature -Uvh ${newest_rpm}/perl-Net-Server*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-Crypt-DES*.i386.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-Net-SNMP-*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-Config-General-*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-HTML-Template*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/rrdtool-*.i386.rpm ${newest_rpm}/perl-rrdtool-*.i386.rpm
$RPM -Uvh ${newest_rpm}/munin-node-*.noarch.rpm
$RPM -Uvh ${newest_rpm}/munin-1*.noarch.rpm

# -> HTML Purifier
echo "Removing installed htmlpurifier if any .."
$RPM -e htmlpurifier 2>/dev/null
$RPM -e htmlpurifier-docs 2>/dev/null
echo "Installing htmlpurifier RPM for Codendi...."
cd ${RPMS_DIR}/htmlpurifier
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/htmlpurifier-3*.noarch.rpm
$RPM -Uvh ${newest_rpm}/htmlpurifier-docs*.noarch.rpm


# -> OpenFire
echo "Removing installed OpenFire if any .."
$RPM -e --allmatches openfire 2>/dev/null
echo "Installing OpenFire Jabber Server...."
cd ${RPMS_DIR}/openfire
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/openfire-*.i386.rpm

echo "Installing OpenFire plugins"
cd ${newest_rpm}
$CP helga.jar presence.jar subscription.jar monitoring.jar /opt/openfire/plugins

#####
# Codendi RPMS

# -> codendi-jri
echo "Removing installed Codendi JRI if any .."
$RPM -e --allmatches codendi-jri 2>/dev/null
echo "Installing Codendi JRI RPM...."
cd ${RPMS_DIR}/codendi-jri
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-jri-*noarch.rpm


# -> codendi-eclipse
echo "Removing installed Eclipse plugin if any .."
$RPM -e --allmatches codendi-eclipse 2>/dev/null
echo "Installing Eclipse plugin RPM...."
cd ${RPMS_DIR}/codendi-eclipse
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-eclipse-*noarch.rpm

# -> codendi-salome-tmf
echo "Removing installed SalomeTMF plugin if any .."
$RPM -e --allmatches codendi-salome-tmf 2>/dev/null
echo "Installing SalomeTMF plugin RPM...."
cd ${RPMS_DIR}/codendi-salome-tmf
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-salome-tmf-*noarch.rpm


##############################################
echo "Prevent yum from auto-updating some packages (cvs, mailman)"

if [ -f /etc/yum.conf ]; then
    $GREP -q ^exclude  /etc/yum.conf
    if [ $? -ne 0 ]; then
        # Add all
        echo "# Codendi specific" >> /etc/yum.conf
        echo "exclude=mailman cvs" >> /etc/yum.conf
    else
        # mailman
        $GREP ^exclude  /etc/yum.conf | $GREP -q mailman
        if [ $? -ne 0 ]; then
             $PERL -i'.orig' -p -e "s/^exclude(.*)/exclude\1 mailman/" /etc/yum.conf
        fi
        # cvs
        $GREP ^exclude  /etc/yum.conf | $GREP -q cvs
        if [ $? -ne 0 ]; then
             $PERL -i'.orig' -p -e "s/^exclude(.*)/exclude\1 cvs/" /etc/yum.conf
        fi
    fi
fi


$PERL -pi -e "s/^#ftpd_banner=.*/ftpd_banner=Welcome to Codendi FTP service./g" /etc/vsftpd/vsftpd.conf 

fi

# CodeX/Codendi specific
exclude=mailman cvs


######
# Now install the non RPMs stuff 
#
# -> saxon

echo "Installing Saxon...."
cd /usr/local
$RM -rf saxon*
$TAR xfz ${nonRPMS_DIR}/docbook/saxon-*.tgz
dir_entry=`$LS -1d saxon-*`
$LN -sf ${dir_entry} saxon

# -> fop
echo "Installing FOP...."
cd /usr/local
$RM -rf fop*
$TAR xfz ${nonRPMS_DIR}/docbook/fop-*.tgz
dir_entry=`$LS -1d fop-*`
$LN -sf ${dir_entry} fop

# -> Jimi
echo "Installing Jimi...."
cd /usr/local
$RM -rf [jJ]imi*
$TAR xfz ${nonRPMS_DIR}/docbook/Jimi-*.tgz
dir_entry=`$LS -1d [jJ]imi-*`
$LN -sf ${dir_entry} jimi

# -> Docbook DTD
echo "Installing DocBook DTD...."
cd /usr/local
$RM -rf docbook-dtd*
$TAR xfz ${nonRPMS_DIR}/docbook/docbook-dtd-*.tgz
dir_entry=`$LS -1d docbook-dtd-*`
$LN -sf ${dir_entry} docbook-dtd

# -> Docbook XSL
echo "Installing DocBook XSL...."
cd /usr/local
$RM -rf docbook-xsl*
$TAR xfz ${nonRPMS_DIR}/docbook/docbook-xsl-*.tgz
dir_entry=`$LS -1d docbook-xsl-*`
$LN -sf ${dir_entry} docbook-xsl

# -> Tomcat (for SalomeTMF)
#echo "Installing tomcat...."
#cd /usr/share
#$TAR xfz ${nonRPMS_DIR}/tomcat/apache-tomcat-6.*.tar.gz
#dir_entry=`$LS -1d apache-tomcat-6.*`
#$RM -f apache-tomcat-6
#$LN -sf ${dir_entry} apache-tomcat-6
#TOMCAT_DIR=/usr/share/apache-tomcat-6
#echo "export JAVA_HOME=/usr/lib/jvm/java-1.6.0-openjdk-1.6.0.0/jre" >> /home/codendiadm/.profile
#echo "export CATALINA_HOME=$TOMCAT_DIR" >> /home/codendiadm/.profile

#echo "Creating tomcat config file..."
#TOMCAT_USERS_XML=$TOMCAT_DIR/conf/tomcat-users.xml
#$CAT <<'EOF' > $TOMCAT_USERS_XML
#<?xml version='1.0' encoding='utf-8'?>
#<tomcat-users>
#  <role rolename="manager"/>
#  <user username="codendiadm" password="$codendiadm_passwd" roles="manager"/>
#</tomcat-users>
#EOF
#$CHMOD 0600 $TOMCAT_USERS_XML
#$CHOWN -R codendiadm.codendiadm $TOMCAT_DIR

echo "Creating MySQL conf file..."
$CAT <<'EOF' >/etc/my.cnf
[client]
default-character-set=utf8

[mysqld]
default-character-set=utf8
log-bin=codendi-bin
skip-bdb
set-variable = max_allowed_packet=128M
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
# Default to using old password format for compatibility with mysql 3.x
# clients (those using the mysqlclient10 compatibility package).
old_passwords=1

# Skip logging openfire db (for instant messaging)
# The 'monitor' openfire plugin creates large codendi-bin files
# Comment this line if you prefer to be safer.
set-variable  = binlog-ignore-db=openfire

[mysql.server]
user=mysql
basedir=/var/lib

[mysqld_safe]
err-log=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EOF

echo "Initializing MySQL: You can ignore additionnal messages on MySQL below this line:"
echo "***************************************"
# Start database
$SERVICE mysqld start
echo "***************************************"


##############################################
# Now install various precompiled utilities
#
cd ${nonRPMS_DIR}/utilities
for f in *
do
  $CP $f /usr/lib/codendi/bin
  $CHOWN codendiadm.codendiadm /usr/lib/codendi/bin/$f
done
$CHOWN root.root /usr/lib/codendi/bin/fileforge
$CHMOD u+s /usr/lib/codendi/bin/fileforge

##############################################
# Install the Codendi software 
#
echo "Installing the Codendi software..."
cd $INSTALL_DIR
$TAR xfz ${Codendi_DIR}/codendi*.tgz
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR
$FIND $INSTALL_DIR -type f -exec $CHMOD u+rw,g+rw,o-w+r {} \;
$FIND $INSTALL_DIR -type d -exec $CHMOD 775 {} \;

echo "Installing configuration files..."
#echo " You should overwrite existing files"
make_backup /etc/httpd/conf/httpd.conf
for f in /etc/httpd/conf/httpd.conf /var/named/chroot/var/named/codendi.zone \
/etc/httpd/conf/ssl.conf \
/etc/httpd/conf.d/php.conf /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/auth_mysql.conf \
/etc/libnss-mysql.cfg  /etc/libnss-mysql-root.cfg \
/etc/codendi/conf/local.inc /etc/codendi/conf/database.inc /etc/httpd/conf.d/codendi_aliases.conf; do
    yn="0"
    fn=`basename $f`
#   [ -f "$f" ] && read -p "$f already exist. Overwrite? [y|n]:" yn
# Always overwrite files
    [ -f "$f" ] && yn="y"

    if [ "$yn" = "y" ]; then
	$CP -f $f $f.orig
    fi

    if [ "$yn" != "n" ]; then
	$CP -f $INSTALL_DIR/src/etc/$fn.dist $f
    fi

    $CHOWN codendiadm.codendiadm $f
    $CHMOD 640 $f
done

$CHOWN root:named /var/named/chroot/var/named/codendi.zone
#$LN -s /var/named/chroot/etc/named.conf /etc
if [ -f "/var/named/chroot/etc/named.conf" ]; then
   $CHGRP named /var/named/chroot/etc/named.conf
fi

if [ $SELINUX_ENABLED ]; then
    $CHCON -h system_u:object_r:named_zone_t /var/named/chroot/var/named/codendi.zone
    if [ -f "/var/named/chroot/etc/named.conf" ]; then
        $CHCON -h system_u:object_r:named_conf_t /var/named/chroot/etc/named.conf
    fi
fi


# Update nsswitch.conf to use libnss-mysql
if [ -f "/etc/nsswitch.conf" ]; then
    # passwd
    $GREP ^passwd  /etc/nsswitch.conf | $GREP -q mysql
    if [ $? -ne 0 ]; then
        $PERL -i'.orig' -p -e "s/^passwd(.*)/passwd\1 mysql/g" /etc/nsswitch.conf
    fi

    # shadow
    $GREP ^shadow  /etc/nsswitch.conf | $GREP -q mysql
    if [ $? -ne 0 ]; then
        $PERL -i'.orig' -p -e "s/^shadow(.*)/shadow\1 mysql/g" /etc/nsswitch.conf
    fi

    # group
    $GREP ^group  /etc/nsswitch.conf | $GREP -q mysql
    if [ $? -ne 0 ]; then
        $PERL -i'.orig' -p -e "s/^group(.*)/group\1 mysql/g" /etc/nsswitch.conf
    fi
else
    echo '/etc/nsswitch.conf does not exist. Cannot use MySQL authentication!'
fi



# Codendi User Guide
# a) copy the local parameters file in custom area and customize it
# b) create the html target directory
# c) create the PDF target directory
#

$CP $INSTALL_DIR/src/etc/ParametersLocal.dtd.dist /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd
$CP $INSTALL_DIR/src/etc/ParametersLocal.cli.dtd.dist /etc/codendi/documentation/cli/xml/ParametersLocal.dtd
# replace string patterns in ParametersLocal.dtd
substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 
substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_org_name%' "$sys_org_name" 
substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_long_org_name%' "$sys_long_org_name" 
# For CLI: only one parameter
substitute '/etc/codendi/documentation/cli/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 

for lang in en_US fr_FR
do
    $MKDIR -p  /etc/codendi/documentation/user_guide/xml/$lang
    $MKDIR -p  /etc/codendi/documentation/cli/xml/$lang
    $MKDIR -p  $INSTALL_DIR/documentation/user_guide/pdf/$lang
    $MKDIR -p  $INSTALL_DIR/documentation/user_guide/html/$lang
    $MKDIR -p  $INSTALL_DIR/documentation/cli/pdf/$lang
    $MKDIR -p  $INSTALL_DIR/documentation/cli/html/$lang
done
$CHOWN -R codendiadm.codendiadm /etc/codendi/documentation
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR/documentation
$CP $INSTALL_DIR/src/utils/backup_job /usr/lib/codendi/bin
$CHOWN root.root /usr/lib/codendi/bin/backup_job
$CHMOD 740 /usr/lib/codendi/bin/backup_job
$CP $INSTALL_DIR/src/utils/svn/backup_subversion.sh /usr/lib/codendi/bin
$CHOWN root:root /usr/lib/codendi/bin/backup_subversion.sh
$CHMOD 740 /usr/lib/codendi/bin/backup_subversion.sh

# replace string patterns in local.inc
substitute '/etc/codendi/conf/local.inc' '%sys_default_domain%' "$sys_default_domain" 
substitute '/etc/codendi/conf/local.inc' '%sys_ldap_server%' "$sys_ldap_server" 
substitute '/etc/codendi/conf/local.inc' '%sys_org_name%' "$sys_org_name" 
substitute '/etc/codendi/conf/local.inc' '%sys_long_org_name%' "$sys_long_org_name" 
substitute '/etc/codendi/conf/local.inc' '%sys_fullname%' "$sys_fullname" 
substitute '/etc/codendi/conf/local.inc' '%sys_dbauth_passwd%' "$dbauth_passwd" 
if [ "$disable_subdomains" = "y" ]; then
  substitute '/etc/codendi/conf/local.inc' 'sys_lists_host = "lists.' 'sys_lists_host = "'
  substitute '/etc/codendi/conf/local.inc' 'sys_disable_subdomains = 0' 'sys_disable_subdomains = 1'
fi
# replace string patterns in codendi_aliases.inc
substitute '/etc/httpd/conf.d/codendi_aliases.conf' '%sys_default_domain%' "$sys_default_domain" 

# replace string patterns in database.inc
substitute '/etc/codendi/conf/database.inc' '%sys_dbpasswd%' "$codendiadm_passwd" 

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

# replace strings in libnss-mysql config files
substitute '/etc/libnss-mysql.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
substitute '/etc/libnss-mysql-root.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
$CHOWN root:root /etc/libnss-mysql.cfg /etc/libnss-mysql-root.cfg
$CHMOD 644 /etc/libnss-mysql.cfg
$CHMOD 600 /etc/libnss-mysql-root.cfg

# replace string patterns in munin.conf (for MySQL authentication)
substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 

if [ "$disable_subdomains" != "y" ]; then
  # replace string patterns in codendi.zone
  sys_shortname=`echo $sys_fullname | $PERL -pe 's/\.(.*)//'`
  dns_serial=`date +%Y%m%d`01
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_default_domain%' "$sys_default_domain" 
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_fullname%' "$sys_fullname"
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_ip_address%' "$sys_ip_address"
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_shortname%' "$sys_shortname"
  substitute '/var/named/chroot/var/named/codendi.zone' '%dns_serial%' "$dns_serial"
fi

# Make sure SELinux contexts are valid
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/codendi
fi

# Create .subversion directory in codendiadm home dir.
su -c 'svn info --non-interactive https://partners.xrce.xerox.com/svnroot/codendi/dev/trunk' - codendiadm 2> /dev/null &


todo "Customize /etc/codendi/conf/local.inc and /etc/codendi/conf/database.inc"
todo "Customize /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd and /etc/codendi/documentation/cli/xml/ParametersLocal.dtd"
todo "You may also want to customize /etc/httpd/conf/httpd.conf /usr/lib/codendi/bin/backup_job and /usr/lib/codendi/bin/backup_subversion.sh"

##############################################
# Installing phpMyAdmin
#

# Make codendiadm a member of the apache group
# This is needed to use the php session at /var/lib/php/session (e.g. for phpwiki)
$USERMOD -a -G apache codendiadm
# Allow read/write access to DAV lock dir for codendiadm in case we want ot enable WebDAV.
$CHMOD 770 /var/lib/dav/

# Add PmaAbsoluteUri parameter? seems useless now
#$PERL -i'.orig' -p -e "s/(\?\>)/\\\$cfg['PmaAbsoluteUri'] = 'http:\/\/$sys_default_domain\/phpMyAdmin'\;\n\1/;" /var/www/phpMyAdmin/config.inc.php
#todo "If you want to run the site in https only, edit the phpMyAdmin configuration file at /var/www/phpMyAdmin/config.inc.php, and replace 'http' by 'https' for the line \$cfg['PmaAbsoluteUri']"

##############################################
# Installing the Codendi database
#
echo "Creating the Codendi database..."

yn="-"
freshdb=0
pass_opt=""
if [ -d "/var/lib/mysql/codendi" ]; then
    read -p "Codendi Database already exists. Overwrite? [y|n]:" yn
fi

# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing Codendi DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

# Delete the Codendi DB if asked for
if [ "$yn" = "y" ]; then
    $MYSQL -u root $pass_opt -e "drop database codendi"
fi

if [ ! -d "/var/lib/mysql/codendi" ]; then
    freshdb=1
    $MYSQL -u root $pass_opt -e "create database codendi DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
    $CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT ALL PRIVILEGES on *.* to codendiadm@localhost identified by '$codendiadm_passwd' WITH GRANT OPTION;
GRANT ALL PRIVILEGES on *.* to root@localhost identified by '$rt_passwd';
FLUSH PRIVILEGES;
EOF
fi
# Password has changed
pass_opt="--password=$rt_passwd"

if [ $freshdb -eq 1 ]; then
echo "Populating the Codendi database..."
cd $INSTALL_DIR/src/db/mysql/
$MYSQL -u codendiadm codendi --password=$codendiadm_passwd < database_structure.sql   # create the DB
cp database_initvalues.sql /tmp/database_initvalues.sql
substitute '/tmp/database_initvalues.sql' '_DOMAIN_NAME_' "$sys_default_domain"
$MYSQL -u codendiadm codendi --password=$codendiadm_passwd < /tmp/database_initvalues.sql  # populate with init values.
rm -f /tmp/database_initvalues.sql

# Create dbauthuser
$CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT SELECT ON codendi.user to dbauthuser@localhost identified by '$dbauth_passwd';
GRANT SELECT ON codendi.groups to dbauthuser@localhost;
GRANT SELECT ON codendi.user_group to dbauthuser@localhost;
GRANT SELECT ON codendi.session to dbauthuser@localhost;
FLUSH PRIVILEGES;
EOF
fi


##############################################
# Installing the SalomeTMF database
#
echo "Creating the SalomeTMF database..."

yn="-"
freshdb=0
if [ -d "/var/lib/mysql/salome" ]; then
    read -p "Salome Database already exists. Overwrite? [y|n]:" yn
fi

# Delete the Salome DB if asked for
if [ "$yn" = "y" ]; then
    $MYSQL -u root $pass_optl -e "drop database salome"
fi


if [ ! -d "/var/lib/mysql/salome" ]; then
    freshdb=1
    $MYSQL -u root $pass_opt -e "create database salome DEFAULT CHARACTER SET latin1"
    $CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT ALL PRIVILEGES ON salome.* TO salomeadm IDENTIFIED BY '$slm_passwd';
FLUSH PRIVILEGES;
EOF
fi

if [ $freshdb -eq 1 ]; then
echo "Populating the SalomeTMF database..."
cd $INSTALL_DIR/plugins/salome/db
$MYSQL -u salomeadm salome --password="$slm_passwd" < salome_structure.sql   # create the DB
$MYSQL -u salomeadm salome --password="$slm_passwd" < salome_initvalues.sql  # init the DB
fi



##############################################
# SSL Certificate creation

if [ "$create_ssl_certificate" = "y" ]; then
    $INSTALL_DIR/src/utils/generate_ssl_certificate.sh
fi


##############################################
# Mailman configuration
# RPM was intalled previously
#
echo "Configuring Mailman..."

# Setup admin password
/usr/lib/mailman/bin/mmsitepass $mm_passwd

#$LN -sf $MAILMAN_DIR /usr/local/mailman ???

# Update Mailman config
if [ "$disable_subdomains" != "y" ]; then
  LIST_DOMAIN=lists.$sys_default_domain
else
  LIST_DOMAIN=$sys_default_domain
fi

$CAT <<EOF >> /usr/lib/mailman/Mailman/mm_cfg.py
DEFAULT_EMAIL_HOST = '$LIST_DOMAIN'
DEFAULT_URL_HOST = '$LIST_DOMAIN'
add_virtualhost(DEFAULT_URL_HOST, DEFAULT_EMAIL_HOST)

# Remove images from Mailman pages (GNU, Python and Mailman logos)
IMAGE_LOGOS = 0

# Uncomment to run Mailman on secure server only
#DEFAULT_URL_PATTERN = 'https://%s/mailman/'
#PUBLIC_ARCHIVE_URL = 'https://%(hostname)s/pipermail/%(listname)s'

EOF


# Compile file
`python -O /usr/lib/mailman/Mailman/mm_cfg.py`

# Create site wide ML
# Note that if sys_default_domain is not a domain, the script will complain
LIST_OWNER=codendi-admin@$sys_default_domain
if [ "$disable_subdomains" = "y" ]; then
    LIST_OWNER=codendi-admin@$sys_fullname
fi
/usr/lib/mailman/bin/newlist -q mailman $LIST_OWNER $mm_passwd > /dev/null

# Comment existing mailman aliases in /etc/aliases
$PERL -i'.orig' -p -e "s/^mailman(.*)/#mailman\1/g" /etc/aliases


# Add new aliases
cat << EOF >> /etc/aliases

## mailman mailing list
mailman:              "|/usr/lib/mailman/mail/mailman post mailman"
mailman-admin:        "|/usr/lib/mailman/mail/mailman admin mailman"
mailman-bounces:      "|/usr/lib/mailman/mail/mailman bounces mailman"
mailman-confirm:      "|/usr/lib/mailman/mail/mailman confirm mailman"
mailman-join:         "|/usr/lib/mailman/mail/mailman join mailman"
mailman-leave:        "|/usr/lib/mailman/mail/mailman leave mailman"
mailman-owner:        "|/usr/lib/mailman/mail/mailman owner mailman"
mailman-request:      "|/usr/lib/mailman/mail/mailman request mailman"
mailman-subscribe:    "|/usr/lib/mailman/mail/mailman subscribe mailman"
mailman-unsubscribe:  "|/usr/lib/mailman/mail/mailman unsubscribe mailman"

EOF

# Subscribe codendi-admin to this ML
echo $LIST_OWNER | /usr/lib/mailman/bin/add_members -r - mailman

$SERVICE mailman start

##############################################
# Installing and configuring Sendmail
#
echo "##############################################"
echo "Installing sendmail shell wrappers and configuring sendmail..."
cd /etc/smrsh
$LN -sf /usr/lib/codendi/bin/gotohell
#$LN -sf $MAILMAN_DIR/mail/mailman Now done in RPM install

$PERL -i'.orig' -p -e's:^O\s*AliasFile.*:O AliasFile=/etc/aliases,/etc/aliases.codendi:' /etc/mail/sendmail.cf
cat <<EOF >/etc/mail/local-host-names
# local-host-names - include all aliases for your machine here.
$sys_default_domain
lists.$sys_default_domain
users.$sys_default_domain
EOF


# Default: codex-admin is redirected to root
echo "codendi-admin:          root" >> /etc/aliases

todo "Finish sendmail settings (see installation Guide) and create codendi-contact and codendi-admin aliases in /etc/aliases"

##############################################
# CVS configuration
#
echo "Configuring the CVS server and CVS tracking tools..."
$TOUCH /etc/cvs_root_allow
$CHOWN codendiadm.codendiadm /etc/cvs_root_allow
$CHMOD 644 /etc/cvs_root_allow

$CP /etc/xinetd.d/cvs /root/cvs.xinetd.ori

$CAT <<'EOF' >/etc/xinetd.d/cvs
service cvspserver
{
        disable             = no
        socket_type         = stream
        protocol            = tcp
        wait                = no
        user                = root
        server              = /usr/bin/cvs
        server_args         = -f -z3 -T/var/tmp --allow-root-file=/etc/cvs_root_allow pserver
}
EOF

cd $INSTALL_DIR/src/utils/cvs1
$CP log_accum /usr/lib/codendi/bin
$CP commit_prep /usr/lib/codendi/bin
$CP cvssh /usr/lib/codendi/bin
$CP cvssh-restricted /usr/lib/codendi/bin

$CAT <<'EOF' >> /etc/shells
/usr/lib/codendi/bin/cvssh
/usr/lib/codendi/bin/cvssh-restricted
EOF

cd /usr/lib/codendi/bin
$CHOWN codendiadm.codendiadm log_accum commit_prep
$CHMOD 755 log_accum commit_prep cvssh cvssh-restricted
$CHMOD u+s log_accum   # sets the uid bit (-rwsr-xr-x)


##############################################
# Subversion configuration
#
echo "Configuring the Subversion server and tracking tools..."
cd $INSTALL_DIR/src/utils/svn
$CP commit-email.pl codendi_svn_pre_commit.php /usr/lib/codendi/bin
cd /usr/lib/codendi/bin
$CHOWN codendiadm.codendiadm commit-email.pl codendi_svn_pre_commit.php
$CHMOD 755 commit-email.pl codendi_svn_pre_commit.php


##############################################
# Make the system daily cronjob run at 23:58pm
echo "Updating daily cron job in system crontab..."
$PERL -i'.orig' -p -e's/\d+ \d+ (.*daily)/58 23 \1/g' /etc/crontab

##############################################
# FTP server configuration
#

# Configure vsftpd
$PERL -i'.orig' -p -e "s/^#anon_upload_enable=YES/anon_upload_enable=YES/g" /etc/vsftpd/vsftpd.conf 
$PERL -pi -e "s/^#ftpd_banner=.*/ftpd_banner=Welcome to Codendi FTP service./g" /etc/vsftpd/vsftpd.conf 
$PERL -pi -e "s/^local_umask=.*/local_umask=002/g" /etc/vsftpd/vsftpd.conf 

# Add welcome messages
$CAT <<'EOF' > /var/lib/codendi/ftp/.message
********************************************************************
Welcome to Codendi FTP server

On This Site:
/incoming          Place where to upload your new file release
/pub               Projects Anonymous FTP space
*********************************************************************

EOF
$CHOWN ftpadmin.ftpadmin /var/lib/codendi/ftp/.message

# Add welcome messages
$CAT <<'EOF' >/var/lib/codendi/ftp/incoming/.message

Upload new file releases here

EOF
$CHOWN ftpadmin.ftpadmin /var/lib/codendi/ftp/incoming/.message

$SERVICE vsftpd start

##############################################
# Create the custom default page for the project Web sites
#
echo "Creating the custom default page for the project Web sites..."
def_page=/etc/codendi/site-content/en_US/others/default_page.php
yn="y"
[ -f "$def_page" ] && read -p "Custom Default Project Home page already exists. Overwrite? [y|n]:" yn
if [ "$yn" = "y" ]; then
    $MKDIR -p /etc/codendi/site-content/en_US/others
    $CHOWN codendiadm.codendiadm /etc/codendi/site-content/en_US/others
    $CP $INSTALL_DIR/site-content/en_US/others/default_page.php /etc/codendi/site-content/en_US/others/default_page.php
fi

if [ "$disable_subdomains" = "y" ]; then
  echo "Use same-host project web sites"
  $MYSQL -u codendiadm codendi --password=$codendiadm_passwd -e "UPDATE service SET link = IF(group_id = 1, '/www/codendi', '/www/\$projectname/') WHERE short_name = 'homepage' "
fi

todo "Customize /etc/codendi/site-content/en_US/others/default_page.php (project web site default home page)"
todo "Customize site-content information for your site."
todo "  For instance: contact/contact.txt cvs/intro.txt"
todo "  svn/intro.txt include/new_project_email.txt, etc."
##############################################
# Shell Access configuration
#

if [ "$active_shell" = "n" ]; then
    echo "Shell access configuration defaulted to 'No shell account'..."
    $MYSQL -u codendiadm codendi --password=$codendiadm_passwd -e "ALTER TABLE user ALTER COLUMN shell SET DEFAULT '/sbin/nologin'"
fi

##############################################
# DNS Configuration
#
if [ "$disable_subdomains" != "y" ]; then
  todo "Create the DNS configuration files as explained in the Codendi Installation Guide:"
  todo "    update /var/named/chroot/var/named/codendi.zone - replace all words starting with %%."
  todo "    make sure the file is readable by 'other':"
  todo "      > chmod o+r /var/named/chroot/var/named/codendi.zone"
  todo "    edit /etc/named.conf to create the new zone."
fi

##############################################
# Crontab configuration
#
echo "Installing root user crontab..."
$CAT <<'EOF' >/tmp/cronfile
# Once a minute, process Codendi system events
* * * * * (cd /usr/share/codendi/src/utils; ./php-launcher.sh ./process_system_events.php)
#
# Regularly launch a system_check event (e.g. every half-hour) 
0,30 * * * * (cd /usr/share/codendi/src/utils; ./php-launcher.sh ./launch_system_check.php)
#
# run the daily statistics script just a little bit after
# midnight so that it computes stats for the day before
# Run at 0:30 am
30 0 * * * /usr/share/codendi/src/utils/compute_all_daily_stats.sh
#
# run the weekly stats for projects. Run it on Monday morning so that
# it computes the stats for the week before
# Run on Monday at 1am
0 1 * * Mon (cd /usr/share/codendi/src/utils/underworld-root; ./db_project_weekly_metric.pl)
#
# daily incremental backup of subversion repositories
45 23 * * 1-6 /usr/lib/codendi/bin/backup_subversion.sh -i
#
# weekly full backup of subversion repositories (0:15 on Sunday)
15 0 * * Sun /usr/lib/codendi/bin/backup_subversion.sh -noarchives
#
# weekly backup preparation (mysql shutdown, file dump and restart)
45 0 * * Sun /usr/lib/codendi/bin/backup_job

# Delete all files in FTP incoming that are older than 2 weeks (336 hours)
#
0 3 * * * /usr/sbin/tmpwatch -m -f 336 /var/lib/codendi/ftp/incoming
#
# It looks like we have memory leaks in Apache in some versions so restart it
# on Sunday. Do it while the DB is down for backup
50 0 * * Sun /sbin/service httpd restart
#
EOF
crontab -u root /tmp/cronfile

echo "Installing  codendiadm user crontab..."
$CAT <<'EOF' >/tmp/cronfile
# Daily Codendi PHP cron (obsolete documents...)
10 0 * * * /usr/share/codendi/src/utils/php-launcher.sh /usr/share/codendi/src/utils/codendi_daily.php
# Re-generate the Codendi User Guides on a daily basis
00 03 * * * /usr/share/codendi/src/utils/generate_doc.sh
30 03 * * * /usr/share/codendi/src/utils/generate_programmer_doc.sh
45 03 * * * /usr/share/codendi/src/utils/generate_cli_package.sh
EOF
crontab -u codendiadm /tmp/cronfile


##############################################
# Log Files rotation configuration
#
echo "Installing log files rotation..."
$CAT <<'EOF' >/etc/logrotate.d/httpd
/var/log/httpd/access_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd reload > /dev/null || true
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/var/log/codendi/$year/$month"
     destfile="http_combined_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/access_log.1 $destdir/$destfile
    endscript
}
 
/var/log/httpd/vhosts-access_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd reload > /dev/null || true
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     #server=`hostname`
     destdir="/var/log/codendi/$year/$month"
     destfile="vhosts-access_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/vhosts-access_log.1 $destdir/$destfile
    endscript
}
                                                                              
/var/log/httpd/error_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd reload > /dev/null || true
    endscript
}


/var/log/httpd/svn_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd reload > /dev/null || true
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     #server=`hostname`
     destdir="/var/log/codendi/$year/$month"
     destfile="svn_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/svn_log.1 $destdir/$destfile
    endscript
}

EOF
$CHOWN root:root /etc/logrotate.d/httpd
$CHMOD 644 /etc/logrotate.d/httpd


$CAT <<'EOF' >/etc/logrotate.d/vsftpd.log
/var/log/xferlog {
    # ftpd doesn't handle SIGHUP properly
    nocompress
    missingok
    daily
    postrotate
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/var/log/codendi/$year/$month"
     destfile="ftp_xferlog_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/xferlog.1 $destdir/$destfile
    endscript
}
EOF
$CHOWN root:root /etc/logrotate.d/vsftpd.log
$CHMOD 644 /etc/logrotate.d/vsftpd.log

##############################################
# Create Codendi profile script
#

# customize the global profile 
$GREP profile_codendi /etc/profile 1>/dev/null
[ $? -ne 0 ] && \
    cat <<'EOF' >>/etc/profile
# Now the Part specific to Codendi users
#
if [ `id -u` -gt 20000 -a `id -u` -lt 50000 ]; then
        . /etc/profile_codendi
fi
EOF

$CAT <<'EOF' >/etc/profile_codendi
# /etc/profile_codendi
#
# Specific login set up and messages for Codendi users`
 
# All projects this user belong to
 
grplist_id=`id -G`;
grplist_name=`id -Gn`;
 
idx=1
for i in $grplist_id
do
        if [ $i -gt 1000 -a $i -lt 20000 ]; then
                field_list=$field_list"$idx,"
        fi
        idx=$[ $idx + 1]
done
grplist=`echo $grplist_name | cut -f$field_list -d" "`;
 
cat <<EOM
 
-------------------------------------
W E L C O M E   T O   C O D E N D I !
-------------------------------------
                                                                               
You are currently in your user home directory: $HOME
EOM
                                                                               
echo "Your project home directories (Web site) are in:"
for i in $grplist
do
        echo "    - /home/groups/$i"
done

cat <<EOM
Corresponding CVS and Subversion repositories are in /cvsroot and /svnroot
                                                                               
             *** IMPORTANT REMARK ***
The Codendi server hosts very valuable yet publicly available
data. Therefore we recommend that you keep working only in
the directories listed above for which you have full rights
and responsibilities.
                                                                               
EOM
EOF

##############################################
# Make sure all major services are on
#
if [ "$disable_subdomains" != "y" ]; then
  $CHKCONFIG named on
fi
$CHKCONFIG sshd on
$CHKCONFIG httpd on
$CHKCONFIG mysqld on
$CHKCONFIG cvs on
$CHKCONFIG mailman on
$CHKCONFIG munin-node on
$CHKCONFIG vsftpd on
$CHKCONFIG openfire on

$SERVICE httpd restart

# NSCD is the Name Service Caching Daemon.
# It is very useful when libnss-mysql is used for authentication
$CHKCONFIG nscd on

$SERVICE nscd start


##############################################
# Set SELinux contexts and load policies
#
if [ $SELINUX_ENABLED ]; then
    echo "Set SELinux contexts and load policies"
    $INSTALL_DIR/src/utils/fix_selinux_contexts.pl
fi

##############################################
# *Last* step: install plugins
#

echo "Install codendi plugins"
# docman plugin
$CAT $INSTALL_DIR/plugins/docman/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
build_dir /etc/codendi/plugins/docman/etc codendiadm codendiadm 755
$CP $INSTALL_DIR/plugins/docman/etc/docman.inc.dist /etc/codendi/plugins/docman/etc/docman.inc
$CHOWN codendiadm.codendiadm /etc/codendi/plugins/docman/etc/docman.inc
$CHMOD 644 /etc/codendi/plugins/docman/etc/docman.inc

# serverupdate plugin
$CAT $INSTALL_DIR/plugins/serverupdate/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd

# salome plugin
$CAT $INSTALL_DIR/plugins/salome/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
build_dir /etc/codendi/plugins/salome/etc codendiadm codendiadm 755
$CP $INSTALL_DIR/plugins/salome/etc/salome.inc.dist /etc/codendi/plugins/salome/etc/salome.inc
$CP $INSTALL_DIR/plugins/salome/etc/database_salome.inc.dist /etc/codendi/plugins/salome/etc/database_salome.inc
substitute '/etc/codendi/plugins/salome/etc/database_salome.inc' '%sys_salomedbpasswd%' "$slm_passwd" 
$CHOWN codendiadm.codendiadm /etc/codendi/plugins/salome/etc/*
$CHMOD 644 /etc/codendi/plugins/salome/etc/*
java -jar $INSTALL_DIR/plugins/salome/tools/keygen.jar $slm_passwd $INSTALL_DIR/plugins/salome/www/webapps/jdbc_client/cfg/
#java -jar $INSTALL_DIR/plugins/salome/tools/keygen.jar $slm_passwd $TOMCAT_DIR/webapps/salome_tmf-soap-server-3/cfg

#GraphOnTrackers plugin
$CAT $INSTALL_DIR/plugins/graphontrackers/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
$CAT $INSTALL_DIR/plugins/graphontrackers/db/initvalues.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd

# IM plugin
build_dir /etc/codendi/plugins/IM/etc codendiadm codendiadm 755
# Create openfireadm MySQL user
$CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT ALL PRIVILEGES on openfire.* to openfireadm@localhost identified by '$openfire_passwd';
FLUSH PRIVILEGES;
EOF
# Install plugin
$CAT $INSTALL_DIR/plugins/IM/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
# Initialize Jabbex
IM_ADMIN_GROUP='imadmingroup'
IM_ADMIN_USER='imadmin-bot'
IM_ADMIN_USER_PW='1M@dm1n'
IM_MUC_PW='Mu6.4dm1n' # Doesn't need to change
$PHP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/install.php -a -orp $rt_passwd -uod openfireadm -pod $openfire_passwd -ucd dbauthuser -pcd $dbauth_passwd -odb jdbc:mysql://localhost:3306/openfire -cdb jdbc:mysql://localhost:3306/codendi -ouri $sys_default_domain -gjx $IM_ADMIN_GROUP -ujx $IM_ADMIN_USER -pjx $IM_ADMIN_USER_PW -pmuc $IM_MUC_PW

# Hudson plugin
$CAT $INSTALL_DIR/plugins/hudson/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd


##############################################
# Generate Documentation
#
echo "Generating the Codendi Manuals. This will take a few minutes."
su -c "$INSTALL_DIR/src/utils/generate_doc.sh -f" - codendiadm 2> /dev/null &
su -c "$INSTALL_DIR/src/utils/generate_programmer_doc.sh -f" - codendiadm 2> /dev/null &
su -c "$INSTALL_DIR/src/utils/generate_cli_package.sh -f" - codendiadm 2> /dev/null &
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR/documentation
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR/downloads

##############################################
# End of installation
#
todo "If you are behind a proxy, then you need to declare the proxy in two files: "
todo "* sys_proxy in /etc/codendi/conf/local.inc (for external RSS feeds support)"
todo "* /home/codendiadm/.subversion/servers for the Server Update plugin"
todo "In order to enable the subversion update, you also need to type the following commands (as codendiadm):"
todo "     cd /usr/share/codendi/"
todo "     svn status -u --username <your_login_on_partners>"
todo "   Accept the certificate permanently, and type in your password."

todo "If only HTTPS is enabled on the Codendi server:"
todo " * update ENTITY SYS_UPDATE_SITE in /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd (replace 'http' by 'https')"
todo " * WARNING: The Eclipse plugin *requires* a *valid* SSL certificate (from a certified authority). Self-signed certificates *won't* work."
todo "If you wish to use SSL encryption with the Jabber server, you need to import or generate an SSL server into Openfire's web server:"
todo " * Go in Openfire Admin iterface (on port 9090 by def), then: Server Settings -> Server Certificates"
todo "To customize the network gallery, copy /usr/share/codendi/site-content/en_US/layout/osdn_sites.txt to /etc/codendi/site-content/en_US/layout/ and edit it."
todo "Create the shell login files for Codendi users in /etc/skel_codendi"
todo "Change the default login shell if needed in the database (/sbin/nologin or /usr/lib/codendi/bin/cvssh, etc.)"
todo "Last, log in as 'admin' on web server, read/accept the license, and click on 'server update'. Then update the server to the latest available version."
todo ""
todo "Note: Codendi now supports CVSNT and the sserver protocol, but they are not installed by default."
todo "If you plan to use CVSNT, please refer to the installation guide"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"

# End of it
echo "=============================================="
echo "Installation completed successfully!"
$CAT $TODO_FILE

exit 0

