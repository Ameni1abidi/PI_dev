# Explication des Tests Unitaires - Gestion d'Évaluation

## 1. Service ExamenManager (src/Service/ExamenManager.php)

Ce service valide les règles métier pour l'entité Examen:

| Règle Métier | Description | Exception levée |
|--------------|-------------|-----------------|
| **Titre obligatoire** | Le titre ne doit pas être vide | `Le titre est obligatoire` |
| **Durée positive** | La durée doit être > 0 | `La durée doit être positive` |
| **Type valide** | Doit être: quiz, devoir, examen | `Le type doit être: quiz, devoir ou examen` |

### Méthodes du service:
- `validate(Examen $examen): bool` - Valide un examen selon les règles métier
- `canBeTakenAt(Examen $examen, DateTimeInterface $date): bool` - Vérifie si l'examen peut être passé
- `getTimeUntilExam(Examen $examen): ?DateInterval` - Retourne le temps avant l'examen

---

## 2. Classe de Test (tests/Service/ExamenManagerTest.php)

### Tests implémentés:

| Méthode de Test | Ce qu'elle vérifie | Résultat attendu |
|-----------------|---------------------|-------------------|
| `testValidExamen()` | Un examen avec titre, durée positive et type valide | assertTrue |
| `testExamenWithoutTitre()` | Exception si titre vide | InvalidArgumentException |
| `testExamenWithNegativeDuree()` | Exception si durée négative | InvalidArgumentException |
| `testExamenWithZeroDuree()` | Exception si durée nulle | InvalidArgumentException |
| `testExamenWithInvalidType()` | Exception si type invalide | InvalidArgumentException |
| `testExamenWithQuizType()` | Le type "quiz" est accepté | assertTrue |
| `testExamenWithDevoirType()` | Le type "devoir" est accepté | assertTrue |
| `testCanBeTakenAtReturnsTrue()` | Examen peut être passé à la date | assertTrue |
| `testCanBeTakenAtReturnsFalse()` | Examen pas encore disponible | assertFalse |
| `testCanBeTakenAtWithNullDate()` | Sans date d'examen | assertFalse |
| `testGetTimeUntilExam()` | Intervalle de temps | assertInstanceOf |
| `testGetTimeUntilExamWithNullDate()` | Sans date (null) | assertNull |

---

## 3. Résultat de la Commande

### Commande exécutée:
```
bash
php bin/phpunit tests/Service/ExamenManagerTest.php
```

### Sortie:
```
PHPUnit 12.5.14 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.0
Configuration: C:\Users\ameni\PI_dev\myprojet\phpunit.dist.xml

............                                                      12 / 12 (100%)

Time: 00:00.091, Memory: 20.00 MB

OK (12 tests, 16 assertions)
```

### Explication des résultats:

| Élément | Signification |
|---------|---------------|
| `............` | 12 points = 12 tests réussis |
| `OK` | Tous les tests passent |
| `12 tests` | Nombre de méthodes de test |
| `16 assertions` | Nombre total d'assertions vérifiées |
| `Time: 00:00.091` | Temps d'exécution (91 millisecondes) |

---

## 4. Structure des Tests

### Pattern utilisé:
- `$this->assertTrue()` - Vérifie que le résultat est vrai
- `$this->assertFalse()` - Vérifie que le résultat est faux
- `$this->assertNull()` - Vérifie que le résultat est null
- `$this->assertInstanceOf()` - Vérifie le type d'objet
- `$this->expectException()` - Vérifie qu'une exception est levée

### Exemple de test:
```
php
public function testValidExamen()
{
    $examen = new Examen();
    $examen->setTitre('Examen de Mathématiques');
    $examen->setDuree(60);
    $examen->setType('examen');

    $manager = new ExamenManager();
    $this->assertTrue($manager->validate($examen));
}
```

---

## 5. Résumé

✅ 12 tests créés
✅ 16 assertions validées
✅ 3 règles métier testées
✅ Tous les tests passent avec succès
