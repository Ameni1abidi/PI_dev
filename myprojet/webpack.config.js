const path = require('path');

module.exports = {
  entry: './src/index.js', // Chemin vers le fichier d'entrée
  output: {
    path: path.resolve(__dirname, 'dist'), // Dossier de sortie
    filename: 'bundle.js', // Nom du fichier de sortie
  },
  mode: 'development', // Mode de compilation
};