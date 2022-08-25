const VueLoaderPlugin = require('vue-loader/lib/plugin');
const path = require('path');

module.exports = (env) => {
	return {
		entry: {
			consultarUsuario: './App/views/entries/ConsultarUsuario.js',
			//detalharCadastro: './App/views/entries/DetalharCadastro.js',
			configuracaoPerfis: './App/views/entries/ConfiguracaoPerfis.js',
			consultarUsuarioSistema: './App/views/entries/ConsultarUsuarioSistema.js',
			cadastrarUsuarioSistema: './App/views/entries/CadastrarUsuarioSistema.js',
			perfil: './App/views/entries/Perfil.js',
		},
		output: {
			path: path.join(__dirname, 'public/bundles'),
			filename: '[name].js',
			publicPath: path.join(__dirname, 'public/bundles/'),
		},
		module: {
			rules: [
				{ test: /\.js$/, use: 'babel-loader' },
				{ test: /\.vue$/, use: 'vue-loader' },
				{ test: /\.css$/, use: ['vue-style-loader', 'css-loader'] },
			],
		},
		resolve: {
			alias: {
				vue$: env.VUE_ENV == 'dev' ? 'vue/dist/vue.js' : 'vue/dist/vue.min.js',
			},
		},
		plugins: [new VueLoaderPlugin()],
	};
};
