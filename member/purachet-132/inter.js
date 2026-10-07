import React, { useState, useEffect } from "https://esm.sh/react@19"
import { createRoot } from "https://esm.sh/react-dom@19/client"
import { motion } from "https://esm.sh/motion/react"

const IMAGES = [
  { src: 'https://i.imgur.com/204kqgz.jpeg', x: 10, y: -15, rotate: 15 },
  { src: 'https://i.imgur.com/nBksCod.jpeg', x: 0, y: 10, rotate: -20 },
  { src: 'https://i.imgur.com/JDLYJFm.jpeg', x: -10, y: -30, rotate: 10 },
  { src: 'https://i.imgur.com/7c4BovS.jpeg', x: 10, y: -10, rotate: -15 },
  { src: 'https://i.imgur.com/38W5F3l.jpeg', x: 5, y: 10, rotate: 5 },
  { src: 'https://i.imgur.com/ndwsoNf.jpeg', x: 10, y: -20, rotate: -10 },
  { src: 'https://i.imgur.com/HRiPI1C.jpeg', x: 20, y: 40, rotate: 10 },
  { src: 'https://i.imgur.com/Aznco02.jpeg', x: 0, y: -20, rotate: -20 },
]

const App = () => {
  const [isOrganized, setIsOrganized] = useState(false)

  const toggleLayout = () => {
    setIsOrganized(prev => !prev)
  }
  
  useEffect(() => {
    document.addEventListener('click', toggleLayout)
    return () => document.removeEventListener('click', toggleLayout)
  }, [])

  return (
    <div className="flex flex-col items-center gap-12 select-none">
      <div className="text-[#222] text-2xl font-semibold">click anywhere to toggle the layout</div>
      <motion.div className="h-40 flex items-center">
        {IMAGES.map((e, i) => (
          <motion.div 
            key={i} 
            className={`w-30 aspect-[4/5] ${isOrganized ? '' : 'shadow-lg'}`}
            initial={{ opacity: 0, scale: 0.7 }}
            transition={{ type: 'spring', bounce: 0.6 }}
            animate={{
              opacity: 1,
                scale: 1,
              x: isOrganized ? 0 : e.x, 
                y: isOrganized ? 0 : e.y,
                  rotate: isOrganized ? 0 : e.rotate,
            }}>
            <img src={e.src} alt="" className="w-full h-full object-cover" />
          </motion.div>
        ))}
      </motion.div>
      
      <div className="fixed top-4 right-4 opacity-80 italic text-sm">
        collage made with <a href="https://www.carouselly.app/" target="_blank" className="underline">Carouselly</a>
      </div>
    </div>
  )
}

const root = createRoot(document.getElementById("app"))

root.render(<App />)