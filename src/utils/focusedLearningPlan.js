const shuffle = (values) => {
  const result = [...values]
  for (let index = result.length - 1; index > 0; index--) {
    const swapIndex = Math.floor(Math.random() * (index + 1))
    ;[result[index], result[swapIndex]] = [result[swapIndex], result[index]]
  }
  return result
}

export const FOCUSED_MODES = {
  listen: { activity: 'audio-recognition', scored: false, exposureOnly: true },
  choose: { activity: 'multiple-choice', scored: true },
  match: { activity: 'word-match', scored: false },
  speak: { activity: 'speech-recognition', scored: true },
  sentence: { activity: 'sentence-reconstruction', scored: true },
}

export function buildFocusedLearningPlan(levelId, words, mode) {
  const definition = FOCUSED_MODES[mode]
  if (!levelId || !definition || !Array.isArray(words)) return []

  const eligible = mode === 'sentence'
    ? words.filter((word) => String(word.example || '').trim())
    : words
  if (mode === 'match') {
    if (eligible.length < 2) return []
    const word = shuffle(eligible)[0]
    return [{
      key: `${levelId}-focused-${mode}-0`,
      roundIndex: 0,
      activity: definition.activity,
      wordId: word.id,
      scored: false,
      exposureOnly: false,
      focused: true,
    }]
  }

  return shuffle(eligible).map((word, index) => ({
    key: `${levelId}-focused-${mode}-${word.id}-${index}`,
    roundIndex: 0,
    activity: definition.activity,
    wordId: word.id,
    scored: definition.scored,
    exposureOnly: Boolean(definition.exposureOnly),
    focused: true,
  }))
}
