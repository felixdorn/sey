#!/bin/bash
[[ "$0" != "${BASH_SOURCE[0]}" ]] && safe_exit="return" || safe_exit="exit"

script_name=$(basename "$0")

ask_question(){
    # ask_question <question> <default>
    local ANSWER
    read -r -p "$1 ($2): " ANSWER
    echo "${ANSWER:-$2}"
}

confirm(){
    # confirm <question> (default = N)
    local ANSWER
    read -r -p "$1 (y/N): " -n 1 ANSWER
    echo " "
    [[ "$ANSWER" =~ ^[Yy]$ ]]
}

current_directory=$(pwd)
folder_name=$(basename "$current_directory")
package_name=$(ask_question "Package name" "$folder_name")
package_description=$(ask_question "Package description" "$package_name")
class_name=$(echo "$package_name" | sed 's/[-_]/ /g' | awk '{for(j=1;j<=NF;j++){ $j=toupper(substr($j,1,1)) substr($j,2) }}1' | sed 's/[[:space:]]//g')


echo
files=$(grep -E -r -l -i ":name|:description|:namespace" --exclude-dir=vendor ./* ./.github/* | grep -v "$script_name")

echo "This script will replace the above values in all relevant files in the project directory."
if ! confirm "Modify files?" ; then
    $safe_exit 1
fi

echo

for file in $files ; do
    echo "Updating file $file"
    temp_file="$file.temp"
    < "$file" \
    sed "s/:namespace/$class_name/g" \
    | sed "s/:name/$package_name/g" \
    | sed "s/:description/$package_name/g" \
    | sed "/^\*\*Note:\*\* Run/d" \
    > "$temp_file"
    rm -f "$file"
    new_file=`echo $file | sed -e "s/Skeleton/${class_name}/g"`
    mv "$temp_file" "$new_file"
done

if confirm "Execute composer install" ; then
    composer install
fi

if confirm 'Let this script delete itself (since you only need it once)?' ; then
    echo "Delete $0 !"
    rm -- "$0"
fi
