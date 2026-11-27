<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student extends User
{
    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Grade::class, orphanRemoval: true)]
    private Collection $grades;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ClassLevel $classLevel = null;

    public function __construct()
    {
        parent::__construct();
        $this->grades = new ArrayCollection();
    }

    /**
     * @return Collection<int, Grade>
     */
    public function getGrades(): Collection
    {
        return $this->grades;
    }

    public function addGrade(Grade $grade): static
    {
        if (!$this->grades->contains($grade)) {
            $this->grades->add($grade);
            $grade->setStudent($this);
        }

        return $this;
    }

    public function removeGrade(Grade $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getStudent() === $this) {
                $grade->setStudent(null);
            }
        }

        return $this;
    }

    public function getClassLevel(): ?ClassLevel
    {
        return $this->classLevel;
    }

    public function setClassLevel(?ClassLevel $classLevel): static
    {
        $this->classLevel = $classLevel;

        return $this;
    }

    public function getGradeByEval (Evaluation $evaluation): ?Grade
    {
        foreach ($this->getGrades() as $grade){
            if ($grade->getEvaluation() === $evaluation){
                return $grade;
            }
        }
        return null;
    }

    /**
     * Moyenne générale de l'étudiant, ramenée sur 20 en normalisant
     * chaque note par le barème de son évaluation.
     * Les notes absentes (élève absent) ou sans barème valide sont ignorées.
     * Retourne null si l'étudiant n'a aucune note exploitable.
     */
    public function getMoyenneGenerale(): ?float
    {
        $sum = 0;
        $count = 0;

        foreach ($this->grades as $grade) {
            $value = $grade->getGrade();
            $bareme = $grade->getEvaluation()?->getBareme();

            if ($value === null || !$bareme) {
                continue;
            }

            $sum += ((float) $value) / $bareme;
            $count++;
        }

        return $count > 0 ? round(20 * $sum / $count, 2) : null;
    }
}
