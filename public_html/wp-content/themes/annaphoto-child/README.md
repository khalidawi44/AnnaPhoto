# Anna Photo — Child theme

Child theme personnalisé pour **annaphoto.eu**, hérite du parent **1001photo**.

## Structure

```
annaphoto-child/
├── style.css              ← header du theme + CSS principal (variables, overrides)
├── functions.php          ← enqueue parent + child + hooks PHP
├── screenshot.png         ← visuel affiche dans Apparence > Themes (optionnel)
├── assets/
│   ├── css/custom.css     ← CSS additionnels (gros blocs)
│   └── js/                ← JS custom (custom.js sera charge auto s'il existe)
├── inc/                   ← fichiers PHP inclus automatiquement
└── README.md              ← ce fichier
```

## Activation

1. Après la sync GitHub, va dans **WordPress → Apparence → Thèmes**
2. Trouve **Anna Photo — Child**
3. Clique **Activer**

⚠️ **Attention** : quitter le thème `1001Photo` (l'ancien child) peut réinitialiser :
- Les menus (à réassigner via Apparence → Menus)
- Les widgets (à replacer)
- Les réglages Customizer (à refaire — couleurs, logo…)

## Workflow modif

1. Tu me dis ce que tu veux changer (ex : « le logo plus gros », « la galerie en 4 colonnes »)
2. Je modifie `style.css`, `functions.php` ou j'ajoute un fichier dans `assets/`
3. Je commit + push sur `main`
4. Le sync GitHub deploie en 5 min max, purge auto du cache LiteSpeed
5. Anna refresh son site → c'est en ligne

## Variables CSS disponibles

Définies dans `style.css` (utilisables partout) :
- `--ap-color-primary`, `--ap-color-accent`, `--ap-color-text`, etc.
- `--ap-space-xs` (8px) à `--ap-space-xl` (96px)
- `--ap-radius-sm`, `--ap-radius-md`, `--ap-radius-lg`

## Pour ajouter dans la sync

Dans **Outils → Sync GitHub → Liste blanche**, ajoute la ligne :
```
themes/annaphoto-child
```
