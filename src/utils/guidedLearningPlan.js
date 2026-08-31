const shuffle = (values) => {
  const result = [...values]
  for (let index = result.length - 1; index > 0; index--) {
    const swapIndex = Math.floor(Math.random() * (index + 1))
    ;[result[index], result[swapIndex]] = [result[swapIndex], result[index]]
  }
  return result
}

const step = (levelId, roundIndex, activity, word, index, options = {}) => ({
  key: `${levelId}-${roundIndex}-${activity}-${word.id}-${index}`,
  roundIndex,
  activity,
  wordId: word.id,
  scored: false,
  exposureOnly: false,
  ...options,
})

export function buildGuidedLearningPlan(levelId, words) {
  if (!levelId || !Array.isArray(words) || !words.length) return []

  const introduction = words.map((word, index) => step(levelId, 0, 'audio-recognition', word, index, { exposureOnly: true }))
  const recognition = shuffle(words).map((word, index) => step(levelId, 1, 'multiple-choice', word, index, { scored: true }))
  const matching = words.length >= 2 ? [step(levelId, 2, 'word-match', words[0], 0)] : []
  const production = shuffle(words).map((word, index) => step(levelId, 3, 'speech-recognition', word, index, { scored: true }))
  const context = shuffle(words.filter((word) => String(word.example || '').trim()))
    .map((word, index) => step(levelId, 4, 'sentence-reconstruction', word, index, { scored: true }))

  return [...introduction, ...recognition, ...matching, ...production, ...context]
}

export function buildReinforcementPlan(levelId, words, incorrectWordIds) {
  const incorrect = new Set([...incorrectWordIds].map(String))
  return shuffle(words.filter((word) => incorrect.has(String(word.id))))
    .map((word, index) => step(levelId, 5, 'multiple-choice', word, index, { scored: true, reinforcement: true }))
}
