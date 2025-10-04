const express = require('express'); 
const app = express(); 
 
app.get('/', (req, res) =
  res.json({ message: 'Healthcare Backend API is running!' }); 
}); 
 
app.listen(PORT, () =
  console.log(`Backend server running on port ${PORT}`); 
}); 
