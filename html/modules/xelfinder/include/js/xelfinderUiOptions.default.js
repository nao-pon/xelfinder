var xelfinderUiOptions = {
	ui : ['toolbar', 'places', 'tree', 'path', 'stat'],
	uiOptions : {
		cwd : {
			listView : {
				columns : ['perm', 'date', 'size', 'kind', 'owner'],
			}
		}
	},
	commandsOptions : {
		edit : {
			dialogWidth: '80%'
		}
	},
	themes : {
		'dark-slim'     : 'https://nao-pon.github.io/elfinder-theme-manifests/dark-slim.json',
		'material'      : 'https://nao-pon.github.io/elfinder-theme-manifests/material-default.json',
		'material-gray' : 'https://nao-pon.github.io/elfinder-theme-manifests/material-gray.json',
		'material-light': 'https://nao-pon.github.io/elfinder-theme-manifests/material-light.json',
		'bootstrap'     : 'https://nao-pon.github.io/elfinder-theme-manifests/bootstrap.json',
		'moono'         : 'https://nao-pon.github.io/elfinder-theme-manifests/moono.json',
		'win10'         : 'https://nao-pon.github.io/elfinder-theme-manifests/win10.json'
	}
};