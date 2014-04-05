#!/usr/bin/env bash

##
# Function parameters and options (with single dash) manager.
#
# Options (one letter max) can be mixed with parameters.
# Syntax allowed :
#     - 6 options in this example, Xs are optionals standarts parameters :
#     [X] -a [X] -b-c [X] -def [X]
#
# Usage :
# function f () {
#	process_options "$@"
#	isset_option 'f' && echo "OK" || echo "NOK"
#	require_parameter 'my_release'
#	local release="$RETVAL"
#	...
# }
#
#
#
# Copyright (c) 2011 Twenga SA
# Copyright (c) 2012-2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
#
# Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance
# with the License. You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software distributed under the License is distributed
# on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License
# for the specific language governing permissions and limitations under the License.
#
# @copyright 2011 Twenga SA
# @copyright 2012-2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
# @license http://www.apache.org/licenses/LICENSE-2.0
#



# Globals of manager system :
FCT_OPTIONS=''		# concatenation of options without dash separated by spaces.
FCT_PARAMETERS=''	# concatenation of parameters (no option) separated by spaces.
RETVAL=''		# global to avoid subshell...

##
# Analyse parameters and dispatch them between options and standart parameters.
# Options are preceded by the prefix '-' or by an option.
# Fill global arrays $FCT_OPTIONS and $FCT_PARAMETERS. 
# 
# @param string $@ list of parameters to analyze
# @testedby TwgitOptionsHandlerTest
#
function process_options {
    local param
    while [ $# -gt 0 ]; do
        # PB to retrieve the option letter when echo "-n"...
        # This example does not works has awaited : param=`echo "$1" | grep -P '^-[^-]' | sed s/-//g`
        # Du coup ceci ne fonctionne pas : param=`echo "$1" | grep -P '^-[^-]' | sed s/-//g`
        # Other solution :
        [ ${#1} -gt 1 ] && [ ${1:0:1} = '-' ] && [ ${1:1:1} != '-' ] && param="${1:1}" || param=''

        param=$(echo "$param" | sed s/-//g)
        if [ ! -z "$param" ]; then
            FCT_OPTIONS="$FCT_OPTIONS $(echo $param | sed 's/\(.\)/\1 /g')"
        else
            FCT_PARAMETERS="$FCT_PARAMETERS $1"
        fi
        shift
    done
    FCT_PARAMETERS=${FCT_PARAMETERS:1}
}

##
# Is given value part of $FCT_OPTIONS ?
# 
# @param string $1 value to look for, without prefix '-'
# @return 0 if found, 1 otherwise
# @testedby TwgitOptionsHandlerTest
#
function isset_option () {
    local item=$1; shift
    echo " $FCT_OPTIONS " | grep -q " $(echo "$item" | sed 's/\([\.\+\$\*]\)/\\\1/g') "
}

##
# Add given options to $FCT_OPTIONS in order to consider them as actives.
#
# @param string $1 options to add, without prefix '-'
# @testedby TwgitOptionsHandlerTest
#
function set_options () {
    local items=$(echo $1); shift
    if [ ${#items} -ge 1 ]; then
        FCT_OPTIONS="$FCT_OPTIONS $(echo $items | sed 's/\(.\)/\1 /g')"
    fi
}

##
# Pops the next parameters of $FCT_PARAMETERS and add it to $RETVAL global variable.
#
# @param string $1 name of the parameters wanted, which will be used for error message in cas of it was not found
# If name is '-' then parameter is considered as optional.
#
function require_parameter () {
    local name=$1

    # Extract the lot of parameters at the most left :
    local param="${FCT_PARAMETERS%% *}"

    # Updating of parameters still to be parsed :
    FCT_PARAMETERS="${FCT_PARAMETERS:$((${#param}+1))}"

    if [ ! -z "$param" ]; then
        RETVAL=$param
    elif [ "$name" = '-' ]; then
        RETVAL=''
    else
        CUI_displayMsg error "Missing argument <$name>!"
        usage
        exit 1
    fi
}
