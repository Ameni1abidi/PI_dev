const path = require('path');

module.exports = {
  entry: {
    bundle: './src/index.js',
    'forum-emoji': './src/forum-emoji.js',
  },
  output: {
    path: path.resolve(__dirname, 'public', 'dist'),
    filename: '[name].js',
  },
  mode: 'development',
};
