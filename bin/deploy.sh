#!/bin/bash

#####################################################
# 定义变量，需要根据插件和用户修改
#####################################################
MAINFILE="wenprise-pinyin-slug.php"
WP_ORG_USERNAME="iwillhappy1314"
SVN_REPO="https://plugins.svn.wordpress.org/wenprise-pinyin-slug/"
GIT_REPO=https://github.com/${TRAVIS_REPO_SLUG}.git


#####################################################
# 部署检查
#####################################################

# pull request 时不部署
if [[ "false" != "$TRAVIS_PULL_REQUEST" ]]; then
	echo "Not deploying pull requests."
	exit
fi

# 只部署一次
if [[ ! $WP_PULUGIN_DEPLOY ]]; then
	echo "Not deploying."
	exit
fi

# SVN 仓库未定义，发出提醒
if [[ ! $SVN_REPO ]]; then
	echo "SVN repo is not specified."
	exit
fi

#####################################################
# 拉取代码，开始构建
#####################################################

# 创建部署所使用的目录
mkdir build

cd build
BUILT_DIR=$(pwd)

# 检出 SVN
echo "Checking out from $SVN_REPO ..."
svn co -q $SVN_REPO ./svn

# 检出 Git，已经有了，是不是不需要再来一遍了，或者直接 checkout?
echo "Clone from $GIT_REPO ..."
git clone -q $GIT_REPO ./git

# 如果设置了构建脚本，开始构建
cd $BUILT_DIR/git

if [ -e "bin/build.sh" ]; then
	echo "Starting bin/build.sh."
	bash bin/build.sh
fi


#####################################################
# 获取 Git 中的插件版本
#####################################################
READMEVERSION=`grep "Stable tag" $BUILT_DIR/git/readme.txt | awk '{ print $NF}'`
PLUGINVERSION=`grep "Version:" $BUILT_DIR/git/$MAINFILE | awk '{ print $NF}'`


#####################################################
# 同步文件
#####################################################
# 同步 git 仓库到 SVN
cd $BUILT_DIR
echo "同步 Git 仓库到 SVN"

if [[ $TRAVIS_TAG ]]; then
    rsync -a --exclude=".svn" --checksum --delete ./git/ ./svn/trunk/
else
    cp ./git/readme.txt ./svn/trunk/ -f
    cp ./git/assets/ ./svn/assets/ -Rf
fi

# 同步完成后、移除 svn trunk 中的 .git 目录
rm $BUILT_DIR/svn/trunk/.git -Rf


#####################################################
# 设置忽略文件、删除忽略的文件
#####################################################
cd $BUILT_DIR/svn/trunk

# 设置 svn 忽略
if [ -e ".svnignore" ]; then
    echo "svn propset form .svnignore"
    svn propset -q -R svn:ignore -F .svnignore .
fi

# 删除忽略的文件
for file in $(cat ".svnignore" 2>/dev/null)
do
    rm $file -Rf
done


#####################################################
# 执行 SVN 操作
#####################################################
echo "Run svn add"
svn st | grep '^!' | sed -e 's/\![ ]*/svn del -q /g' | sh
echo "Run svn del"
svn st | grep '^?' | sed -e 's/\?[ ]*/svn add -q /g' | sh


#####################################################
# 如果设置了用户名密码，提交到仓库，必须是 Tag 才能提交
#####################################################
cd $BUILT_DIR/svn

svn add *
svn add ./trunk/readme.txt
svn stat

# todo: 标签应该用 Git 标签还是插件版本号？
if [[ $TRAVIS_TAG ]]; then

    #####################################################
    # 比较版本，如果两个版本不一样，退出
    #####################################################

    if [ "$READMEVERSION" != "$PLUGINVERSION" ]; then
        echo "Versions don't match. Exiting....";
        exit 1
    fi

    # 发布到 wordpress.org
    echo "发布到 wordpress.org";
	svn ci --no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_PASSWORD -m "Deploy version $READMEVERSION"

	# 打标签
	echo "打标签";
    svn copy --no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_PASSWORD $SVN_REPO/trunk $SVN_REPO/tags/$READMEVERSION -m "Add tag $READMEVERSION"

	echo "发布新版本完成";

else

    cd $BUILT_DIR/svn/trunk

	svn ci --no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_PASSWORD -m "`Deploy readme.txt`"

	echo "更新 assets 和 readme.txt 完成";
fi