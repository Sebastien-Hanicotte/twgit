#!/bin/bash

assert_git_repository

function usage () {
	echo; help 'Usage:'
	help_detail 'twgit release <action>'
	echo; help 'Available actions are:'
	help_detail '<b>list</b>'
	help_detail '    List remote releases. Add <b>-n</b> to do not pre fetch.'; echo
	help_detail '<b>finish <releasename> [<tagname>]</b>'
	help_detail '    Merge specified release branch into master, create a new tag and push.'
	help_detail '    If no <tagname> is specified then <releasename> will be used.'; echo
	help_detail '<b>remove <releasename></b>'
	help_detail '    Remove both local and remote specified release branch.'; echo
	help_detail '<b>reset <releasename></b>'
	help_detail '    Call remove <releasename> and start <releasename>'; echo
	help_detail '<b>start [<releasename>] [-M|-m|-r]</b>'
	help_detail '    Create both a new local and remote release,'
	help_detail '    or fetch the remote release if <releasename> exists on remote repository.'
	help_detail "    Prefix '$TWGIT_PREFIX_RELEASE' will be added to the specified <releasename>."
	help_detail '    If no <releasename> is specified, a name will be generated from last tag:'
	help_detail '        <b>-M</b> for a new major version'
	help_detail '        <b>-m</b> for a new minor version (default)'
	help_detail '        <b>-r</b> for a new revision version'; echo
	help_detail '<b>[help]</b>'
	help_detail '    Display this help.'; echo
}

function cmd_help () {
	usage
	exit 0
}

function cmd_list () {
	process_options "$@"
	process_fetch 'n'
	
	local releases=$(git branch -r --merged $TWGIT_ORIGIN/HEAD | grep "$TWGIT_ORIGIN/$TWGIT_PREFIX_RELEASE" | sed 's/^[* ]*//')
	help "Remote releases merged into master:"
	if [ -z "$releases" ]; then
		info 'No merged release branch exists.'; echo
	else
		display_branches 'Release: ' "$releases"
	fi
		
	local releases=$(git branch -r --no-merged $TWGIT_ORIGIN/HEAD | grep "$TWGIT_ORIGIN/$TWGIT_PREFIX_RELEASE" | sed 's/^[* ]*//')
	help "Remote releases NOT merged into master (normally at most one):"
	if [ -z "$releases" ]; then
		info 'No release branch NOT merged exists.'; echo
	else
		[ $(echo "$releases" | wc -w) -ge 2 ] && warn "No more one release should be listed here!"
		display_branches 'Release: ' "$releases"
	fi
}

function cmd_start () {
	process_options "$@"
	require_parameter '-'
	local release="$RETVAL"
	local release_fullname
	
	assert_tag_exists
	local last_tag=$(get_last_tag)
	local short_last_tag=${last_tag:${#TWGIT_PREFIX_TAG}}
	
	if [ -z $release ]; then
		local type
		if isset_option 'M'; then type='major'
		elif isset_option 'm'; then type='minor'
		elif isset_option 'r'; then type='revision'
		else type='minor'
		fi
		release=$(get_next_version $type $short_last_tag)
		release_fullname="$TWGIT_PREFIX_RELEASE$release"
		echo "Release: $release_fullname"
		echo -n "Do you want to continue? [Y/N] "; read answer
		[ "$answer" != "Y" ] && [ "$answer" != "y" ] && die 'New release aborted!'
	else
		release_fullname="$TWGIT_PREFIX_RELEASE$release"
	fi
	
	assert_valid_ref_name $release
	assert_clean_working_tree
	assert_new_local_branch $release_fullname
	
	process_fetch
	
	processing 'Check remote releases...'
	local is_remote_exists=$(has "$TWGIT_ORIGIN/$release_fullname" $(get_remote_branches) && echo 1 || echo 0)
	if [ $is_remote_exists = '1' ]; then
		processing "Remote release '$release_fullname' detected."
	fi	
	
	process_git_command "git checkout -b $release_fullname $last_tag" "Could not check out tag '$last_tag'!"
	
	process_first_commit 'release' "$release_fullname"
	process_push_branch $release_fullname $is_remote_exists
}

function cmd_finish () {
	process_options "$@"
	require_parameter 'release'
	local release="$RETVAL"
	local release_fullname="$TWGIT_PREFIX_RELEASE$release"

	require_parameter '-'
	local tag="$RETVAL"
	[ -z "$tag" ] && tag="$TWGIT_PREFIX_TAG$release"
	local tag_fullname="$TWGIT_PREFIX_TAG$tag"
	
	assert_clean_working_tree
	
	process_fetch
	
	processing 'Check remote releases...'
	local is_release_exists=$(has "$TWGIT_ORIGIN/$release_fullname" $(get_remote_branches) && echo 1 || echo 0)
	[ $is_release_exists = '0' ] && die "Unknown '$release_fullname' remote release! Try: twgit release list"
	
	has $release_fullname $(get_local_branches) && assert_branches_equal "$release_fullname" "$TWGIT_ORIGIN/$release_fullname"
	
	assert_valid_tag_name $tag_fullname
	processing 'Check tags...'
	local is_tag_exists=$(has "$tag_fullname" $(get_all_tags) && echo 1 || echo 0)
	[ $is_tag_exists = '1' ] && die "Tag '$tag_fullname' already exists! Try: twgit tag list"
	exit
	
	process_git_command "git checkout $TWGIT_MASTER" "Could not checkout '$TWGIT_ORIGIN'!"
	process_git_command "git merge --no-ff $TWGIT_ORIGIN/$TWGIT_MASTER" "Could not merge '$TWGIT_ORIGIN/$TWGIT_MASTER' into '$TWGIT_MASTER'!"
	process_git_command "git merge --no-ff $release_fullname" "Could not merge '$release_fullname' into '$TWGIT_MASTER'!"
	process_git_command "git tag -a $tag_fullname -m \"${TWGIT_PREFIX_COMMIT_MSG}Release finish: $release_fullname\"" "Could not tag '$TWGIT_MASTER'!"
	process_git_command "git push --tags $TWGIT_ORIGIN $TWGIT_MASTER" "Could not push '$TWGIT_MASTER' on '$TWGIT_ORIGIN'!"
}

function cmd_remove () {
	process_options "$@"
	require_parameter 'release'
	local release="$RETVAL"
	local release_fullname="$TWGIT_PREFIX_RELEASE$release"
	
	assert_valid_ref_name $release
	assert_working_tree_is_not_to_delete_branch $release_fullname
	
	process_fetch
	remove_local_branch $release_fullname
	remove_remote_branch $release_fullname
}

function cmd_reset () {
	process_options "$@"
	require_parameter 'release'
	local release="$RETVAL"
	cmd_remove $release && cmd_start $release
}
